<?php

namespace App\Services;

use App\Models\DryerProcess;
use App\Models\SensorData;
use App\Models\SensorDevice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\Exceptions\MqttClientException;
use PhpMqtt\Client\ConnectionSettings;

class MQTTService
{
    /** @var MqttClient */
    protected $client;

    /** @var string */
    protected $host = 'broker.hivemq.com';
    /** @var int */
    protected $port = 1883;

    /** @var string unique client id */
    protected $clientId;

    /** @var int keep alive seconds */
    protected $keepAlive = 30;

    /** @var int loop sleep microseconds */
    protected $loopSleepMicros = 200000; // 0.2s

    /** @var string wildcard untuk pendaftaran perangkat */
    protected $registrationWildcard = 'iot/+/+/+/connect';

    /** @var array<int, array> dryer_id => [ device_id => meta, ... ] */
    protected $devicesByDryer = [];

    /** @var array<string, array> topic => meta */
    protected $topicMap = [];

    /** @var array<string, bool> seluruh topic telemetry yang saat ini disubscribe */
    protected $currentSubscriptions = [];

    /** @var array<int, array<int, array>> buffers per dryer: buffers[dryer_id][device_id] = data array */
    protected $buffers = [];

    /** @var int detik antara refresh daftar device dari DB */
    protected $refreshInterval = 15;

    /** @var int timestamp terakhir refresh */
    protected $lastRefreshAt = 0;

    /** @var int window maksimal umur data buffer (detik) untuk dianggap satu batch */
    protected $bufferWindowSeconds = 60;

    /** @var bool flag untuk menghindari reconnect bersamaan */
    protected $isReconnecting = false;

    public function __construct()
    {
        $this->clientId = 'laravel-client-' . uniqid('', true);
        $this->connectAndBootstrap();
    }

    public function subscribe()
    {
        Log::info('MQTT loop started');

        while (true) {
            try {
                if (!$this->client || !$this->client->isConnected()) {
                    $this->attemptReconnect('disconnected in loop');
                    usleep($this->loopSleepMicros);
                    continue;
                }

                $this->client->loop(true);
                $this->maybeRefreshDevices();

                usleep($this->loopSleepMicros);
            } catch (\Throwable $e) {
                Log::error('MQTT loop exception: ' . $e->getMessage());
                $this->attemptReconnect('loop exception');
            }
        }
    }

    protected function connectAndBootstrap(): void
    {
        $this->connectClient();
        $this->subscribeRegistrationWildcard();
        $this->reloadDevicesAndSyncSubscriptions(true);
    }

    protected function connectClient(): void
    {
        try {
            if ($this->client) {
                $this->client->disconnect();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        $this->client = new MqttClient($this->host, $this->port, $this->clientId);

        $settings = new ConnectionSettings();
        if (method_exists($settings, 'setKeepAliveInterval')) {
            $settings->setKeepAliveInterval($this->keepAlive);
        }

        $this->client->connect($settings, true);

        Log::info('MQTT connected', [
            'host' => $this->host,
            'port' => $this->port,
            'client_id' => $this->clientId,
            'keep_alive' => $this->keepAlive,
        ]);
    }

    protected function attemptReconnect(string $reason): void
    {
        if ($this->isReconnecting) return;
        $this->isReconnecting = true;

        Log::warning("Attempting MQTT reconnect: {$reason}");

        $attempt = 0;
        $maxDelay = 10; // seconds
        while (true) {
            try {
                $delay = min($maxDelay, 1 << min($attempt, 3)); // 1,2,4,8,8,8...
                if ($attempt > 0) sleep($delay);

                $this->connectClient();
                $this->subscribeRegistrationWildcard();
                $this->reloadDevicesAndSyncSubscriptions(true);

                $this->isReconnecting = false;
                Log::info('MQTT reconnected successfully');
                return;
            } catch (\Throwable $e) {
                $attempt++;
                Log::error("Reconnect attempt #{$attempt} failed: " . $e->getMessage());
            }
        }
    }

    protected function subscribeRegistrationWildcard(): void
    {
        try {
            $this->client->subscribe($this->registrationWildcard, function ($topic, $payload) {
                $this->handleConnectMessage($topic, $payload);
            }, 0);
            Log::info("Subscribed registration wildcard: {$this->registrationWildcard}");
        } catch (\Throwable $e) {
            Log::error('Failed to subscribe registration wildcard: ' . $e->getMessage());
        }
    }

    protected function maybeRefreshDevices(): void
    {
        $now = time();
        if ($now - $this->lastRefreshAt >= $this->refreshInterval) {
            $this->reloadDevicesAndSyncSubscriptions(false);
        }
    }

    protected function reloadDevicesAndSyncSubscriptions(bool $initial): void
    {
        $this->lastRefreshAt = time();

        $devices = SensorDevice::where('status', true)
            ->select('device_id', 'address', 'dryer_id')
            ->get();

        $newTopicMap = [];
        $newDevicesByDryer = [];
        foreach ($devices as $d) {
            if (empty($d->address) || empty($d->dryer_id)) continue;

            $meta = [
                'device_id' => (int) $d->device_id,
                'address'   => (string) $d->address,
                'dryer_id'  => (int) $d->dryer_id,
            ];
            $newTopicMap[$d->address] = $meta;
            $newDevicesByDryer[$d->dryer_id][$d->device_id] = $meta;
        }

        $oldTopics = array_keys($this->currentSubscriptions);
        $newTopics = array_keys($newTopicMap);

        $toSubscribe   = array_diff($newTopics, $oldTopics);
        $toUnsubscribe = array_diff($oldTopics, $newTopics);

        foreach ($toUnsubscribe as $topic) {
            try {
                $this->client->unsubscribe($topic);
                unset($this->currentSubscriptions[$topic]);
                Log::info("Unsubscribed telemetry: {$topic}");
            } catch (\Throwable $e) {
                Log::warning("Failed to unsubscribe {$topic}: " . $e->getMessage());
            }

            if (isset($this->topicMap[$topic])) {
                $dryerId  = $this->topicMap[$topic]['dryer_id'];
                $deviceId = $this->topicMap[$topic]['device_id'];
                unset($this->buffers[$dryerId][$deviceId]);
                if (empty($this->buffers[$dryerId])) unset($this->buffers[$dryerId]);
            }
        }

        foreach ($toSubscribe as $topic) {
            $this->subscribeTelemetryTopic($topic, $newTopicMap[$topic] ?? null);
        }

        $this->topicMap = $newTopicMap;
        $this->devicesByDryer = $newDevicesByDryer;

        $label = $initial ? 'Initial' : 'Refreshed';
        Log::info("{$label} telemetry topics: " . implode(', ', array_keys($this->currentSubscriptions)));
    }

    protected function subscribeTelemetryTopic(string $topic, ?array $meta): void
    {
        if (!$meta) return;

        try {
            $this->client->subscribe($topic, function ($incomingTopic, $message) {
                $this->handleTelemetryMessage($incomingTopic, $message);
            }, 0);
            $this->currentSubscriptions[$topic] = true;

            $this->topicMap[$topic] = $meta;
            $this->devicesByDryer[$meta['dryer_id']][$meta['device_id']] = $meta;

            Log::info("Subscribed telemetry: {$topic}");
        } catch (\Throwable $e) {
            Log::error("Failed telemetry subscribe {$topic}: " . $e->getMessage());
        }
    }

    /**
     * Handler CONNECT: Upsert SensorDevice by (dryer_id, address).
     * Topic: iot/mitra{mitraId}/dryer{dryerId}/{panelId}/connect
     */
    protected function handleConnectMessage(string $topic, string $payload): void
    {
        Log::info('Connect message', ['topic' => $topic, 'payload' => $payload]);

        try {
            $data = json_decode($payload, true);
            if (!is_array($data)) {
                Log::error('Connect payload invalid JSON.');
                return;
            }

            $mitraId = isset($data['mitra_id']) ? (int)$data['mitra_id'] : null;
            $dryerId = isset($data['dryer_id']) ? (int)$data['dryer_id'] : null;
            $panelId = isset($data['panel_id']) ? (int)$data['panel_id'] : null;

            $parts = explode('/', $topic); // [iot, mitraX, dryerY, panel, connect]
            if (count($parts) >= 5) {
                if ($mitraId === null && preg_match('/^mitra(\d+)$/', $parts[1], $m)) $mitraId = (int)$m[1];
                if ($dryerId === null && preg_match('/^dryer(\d+)$/', $parts[2], $m)) $dryerId = (int)$m[1];
                if ($panelId === null && is_numeric($parts[3]))                                   $panelId = (int)$parts[3];
            }

            if (!$dryerId || !$panelId) {
                Log::error('Missing dryer_id/panel_id in connect message.');
                return;
            }

            $deviceName   = (string)($data['device_name'] ?? "Panel {$panelId}");
            $location     = (string)($data['location'] ?? null);
            $mqttBase     = (string)($data['mqtt_topic_base'] ?? "iot/mitra{$mitraId}/dryer{$dryerId}");
            $telemetry    = (string)($data['telemetry_topic'] ?? "{$mqttBase}/{$panelId}");

            // ---------- UPSERT by (dryer_id, address) ----------
            $attributes = ['dryer_id' => $dryerId, 'address' => $telemetry];
            $values     = ['status' => true];

            if ($this->schemaHas('sensor_devices', 'device_name')) $values['device_name'] = $deviceName;
            if ($this->schemaHas('sensor_devices', 'location'))    $values['location']    = $location;
            if ($mitraId && $this->schemaHas('sensor_devices', 'mitra_id')) $values['mitra_id'] = $mitraId;

            $device = null;

            try {
                // Gunakan updateOrCreate agar tidak pernah menyentuh device_id secara manual
                $device = SensorDevice::updateOrCreate($attributes, $values);
            } catch (\Throwable $e) {
                // Jika terjadi unique violation (mis. sequence/ race), ambil ulang dan update manual
                $msg = $e->getMessage();
                if (strpos($msg, '23505') !== false || stripos($msg, 'unique') !== false) {
                    Log::warning('Unique violation on SensorDevice upsert, retrying fetch & update...', ['error' => $msg, 'attrs' => $attributes]);
                    $device = SensorDevice::where($attributes)->first();
                    if ($device) {
                        $device->update($values);
                    } else {
                        // fallback terakhir: coba create dalam transaction
                        DB::beginTransaction();
                        try {
                            $device = SensorDevice::create(array_merge($attributes, $values));
                            DB::commit();
                        } catch (\Throwable $e2) {
                            DB::rollBack();
                            Log::error('Second attempt create SensorDevice failed: ' . $e2->getMessage());
                            return;
                        }
                    }
                } else {
                    Log::error('Error upserting SensorDevice: ' . $msg);
                    return;
                }
            }

            Log::info('SensorDevice upserted', [
                'device_id' => $device->device_id,
                'dryer_id'  => $device->dryer_id,
                'address'   => $device->address,
            ]);

            // Pastikan telemetry topic tersubscribe segera
            if (empty($this->currentSubscriptions[$telemetry])) {
                $meta = [
                    'device_id' => (int)$device->device_id, // ini PK dari DB, bukan panel_id
                    'address'   => $telemetry,
                    'dryer_id'  => (int)$device->dryer_id,
                ];
                $this->subscribeTelemetryTopic($telemetry, $meta);
            }
        } catch (\Throwable $e) {
            Log::error('Error in handleConnectMessage: ' . $e->getMessage());
        }
    }

    protected function handleTelemetryMessage(string $topic, string $message): void
    {
        if (fnmatch('iot/*/*/*/connect', $topic)) return;

        Log::info('Received MQTT message', ['topic' => $topic, 'message' => $message]);

        try {
            $meta = $this->topicMap[$topic] ?? null;
            if (!$meta) {
                Log::warning('Message on unknown telemetry topic', ['topic' => $topic]);
                return;
            }

            $deviceId = (int) $meta['device_id']; // PK sensor_devices
            $dryerId  = (int) $meta['dryer_id'];

            $data = json_decode($message, true);
            if (!is_array($data)) {
                Log::error('Invalid JSON telemetry', ['topic' => $topic]);
                return;
            }

            // Simpan raw event
            $dryingProcess = DryerProcess::where('dryer_id', $dryerId)
                ->whereIn('status', ['pending', 'ongoing'])
                ->first();

            if (!$dryingProcess) {
                $dryingProcess = DryerProcess::create([
                    'dryer_id'           => $dryerId,
                    'status'             => 'pending',
                    'timestamp_mulai'    => null,
                    'grain_type_id'      => null,
                    'berat_gabah_awal'   => null,
                    'kadar_air_target'   => null,
                    'durasi_rekomendasi' => 0,
                ]);
            }

            $row = [
                'process_id'      => $dryingProcess->process_id,
                'device_id'       => $deviceId,
                'timestamp'       => now(),
                'kadar_air_gabah' => isset($data['grain_moisture'])     ? (float) $data['grain_moisture']     : null,
                'suhu_gabah'      => isset($data['grain_temperature'])  ? (float) $data['grain_temperature']  : null,
                'suhu_ruangan'    => isset($data['room_temperature'])   ? (float) $data['room_temperature']   : null,
                'suhu_pembakaran' => isset($data['burning_temperature'])? (float) $data['burning_temperature']: null,
                'status_pengaduk' => array_key_exists('stirrer_status', $data) ? (bool) $data['stirrer_status'] : null,
            ];
            SensorData::create($row);

            // Buffer batch per-dryer
            $this->buffers[$dryerId][$deviceId] = $row;

            $expectedDevices = isset($this->devicesByDryer[$dryerId]) ? array_keys($this->devicesByDryer[$dryerId]) : [];
            $gotDevices      = isset($this->buffers[$dryerId]) ? array_keys($this->buffers[$dryerId]) : [];

            if (!empty($expectedDevices) && $this->hasAllDevices($expectedDevices, $gotDevices)) {
                if ($this->isBufferFresh($this->buffers[$dryerId])) {
                    $this->processAndSendData($dryingProcess, $dryerId);
                } else {
                    $this->buffers[$dryerId] = [];
                }
            }
        } catch (\Throwable $e) {
            Log::error('Error processing telemetry: ' . $e->getMessage());
        }
    }

    protected function hasAllDevices(array $expectedDeviceIds, array $gotDeviceIds): bool
    {
        sort($expectedDeviceIds);
        sort($gotDeviceIds);
        return $expectedDeviceIds === $gotDeviceIds;
    }

    protected function isBufferFresh(array $bufferForDryer): bool
    {
        if (empty($bufferForDryer)) return false;
        $now = time();
        $minTs = PHP_INT_MAX;
        foreach ($bufferForDryer as $row) {
            $ts = isset($row['timestamp']) ? strtotime($row['timestamp']) : $now;
            if ($ts < $minTs) $minTs = $ts;
        }
        return ($now - $minTs) <= $this->bufferWindowSeconds;
    }

    protected function processAndSendData(DryerProcess $dryingProcess, int $dryerId): void
    {
        try {
            $buffer = $this->buffers[$dryerId] ?? [];
            if (empty($buffer)) return;

            if (is_null($dryingProcess->grain_type_id)
                || is_null($dryingProcess->berat_gabah_awal)
                || is_null($dryingProcess->kadar_air_target)) {
                $this->buffers[$dryerId] = [];
                return;
            }

            $suhu_gabah_values = [];
            $kadar_air_values  = [];
            $suhu_ruang_values = [];
            $suhu_bakar_values = [];
            $stirrer_values    = [];

            foreach ($buffer as $row) {
                if (!is_null($row['suhu_gabah']))      $suhu_gabah_values[] = (float) $row['suhu_gabah'];
                if (!is_null($row['kadar_air_gabah'])) $kadar_air_values[]  = (float) $row['kadar_air_gabah'];
                if (!is_null($row['suhu_ruangan']))    $suhu_ruang_values[] = (float) $row['suhu_ruangan'];
                if (!is_null($row['suhu_pembakaran'])) $suhu_bakar_values[] = (float) $row['suhu_pembakaran'];
                if (!is_null($row['status_pengaduk'])) $stirrer_values[]    = (bool)  $row['status_pengaduk'];
            }

            if (empty($suhu_gabah_values) || empty($kadar_air_values) || empty($suhu_ruang_values) || empty($suhu_bakar_values) || empty($stirrer_values)) {
                $this->buffers[$dryerId] = [];
                return;
            }

            $payload = [
                'process_id'       => $dryingProcess->process_id,
                'grain_type_id'    => $dryingProcess->grain_type_id,
                'suhu_gabah'       => number_format(array_sum($suhu_gabah_values) / count($suhu_gabah_values), 7, '.', ''),
                'kadar_air_gabah'  => number_format(array_sum($kadar_air_values)  / count($kadar_air_values),  7, '.', ''),
                'suhu_ruangan'     => number_format(array_sum($suhu_ruang_values) / count($suhu_ruang_values), 7, '.', ''),
                'suhu_pembakaran'  => number_format(array_sum($suhu_bakar_values) / count($suhu_bakar_values), 7, '.', ''),
                'status_pengaduk'  => (bool) reset($stirrer_values),
                'kadar_air_target' => (float) $dryingProcess->kadar_air_target,
                'weight'           => (float) $dryingProcess->berat_gabah_awal,
                'timestamp'        => time(),
            ];

            if (env('ML_API')) {
                $response = Http::timeout(10)->post(rtrim(env('ML_API'), '/') . '/predict-now', $payload);
                if (!$response->successful()) {
                    Log::error('Failed ML POST', ['status' => $response->status(), 'body' => $response->body()]);
                }
            } else {
                Log::warning('ML_API env not set; skip prediction POST.');
            }

            $this->buffers[$dryerId] = [];
            Log::info('Batch processed & sent', ['dryer_id' => $dryerId, 'process_id' => $dryingProcess->process_id]);
        } catch (\Throwable $e) {
            Log::error('Error processing batch: ' . $e->getMessage());
            $this->buffers[$dryerId] = [];
        }
    }

    public function stop()
    {
        try {
            if ($this->client) {
                $this->client->disconnect();
                Log::info('MQTT client disconnected');
            }
        } catch (MqttClientException $e) {
            Log::error('Failed to disconnect MQTT client: ' . $e->getMessage());
        }
    }

    protected function schemaHas(string $table, string $column): bool
    {
        try {
            return Schema::hasColumn($table, $column);
        } catch (\Throwable $e) {
            Log::warning("schemaHas failed for {$table}.{$column}: " . $e->getMessage());
            return false;
        }
    }
}
