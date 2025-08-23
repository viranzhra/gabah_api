<?php

namespace App\Services;

use App\Models\DryerProcess;
use App\Models\SensorData;
use App\Models\SensorDevice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\Exceptions\MqttClientException;

class MQTTService
{
    protected $client;
    protected $topics = [];
    protected $sensorDataBuffer = [];

    public function __construct()
    {
        $this->topics = SensorDevice::where('status', true)
            ->select('device_id', 'address')
            ->get()
            ->keyBy('device_id');

        if ($this->topics->isEmpty()) {
            Log::warning('No active sensor devices found in database');
        }

        $this->client = new MqttClient('localhost', 1883, 'laravel-client-' . uniqid());
        // $this->client = new MqttClient('broker.hivemq.com', 1883, 'laravel-client-' . uniqid());
        // $this->client->connect(null, true, [
        //     'username' => 'graindryer',
        //     'password' => 'polindra'
        // ]);
        $this->client->connect();
    }

    public function subscribe()
    {
        try {
            foreach ($this->topics as $device) {
                $this->client->subscribe($device->address, function ($topic, $message) {
                    $this->handleMessage($topic, $message);
                }, 0);
            }
            Log::info('Subscribed to MQTT topics: ' . implode(', ', $this->topics->pluck('address')->toArray()));
            $this->client->loop(true);
        } catch (MqttClientException $e) {
            Log::error('Failed to subscribe to MQTT topics: ' . $e->getMessage());
        }
    }

    protected function handleMessage($topic, $message)
    {
        Log::info('Received MQTT message', ['topic' => $topic, 'message' => $message]);
        try {
            $data = json_decode($message, true);
            if (!$data || !isset($data['panel_id'])) {
                Log::error('Invalid MQTT message format', ['topic' => $topic, 'message' => $message]);
                return;
            }

            $panel_id = (int) $data['panel_id'];
            $device = $this->topics->get($panel_id);
            if (!$device || $device->address !== $topic) {
                Log::error('Invalid panel_id or topic mismatch', ['panel_id' => $panel_id, 'topic' => $topic]);
                return;
            }

            // Buat atau ambil DryingProcess
            $dryingProcess = DryerProcess::whereIn('status', ['pending', 'ongoing'])->first();
            if (!$dryingProcess) {
                $dryingProcess = DryerProcess::create([
                    'status' => 'pending',
                    'user_id' => 1, // Default user_id
                    'timestamp_mulai' => null,
                    'grain_type_id' => null,
                    'berat_gabah_awal' => null,
                    'kadar_air_target' => null,
                    'durasi_rekomendasi' => 0,
                ]);
                Log::info('Created new pending drying process', ['process_id' => $dryingProcess->process_id]);
            }

            // Simpan data sementara di buffer
            $this->sensorDataBuffer[$panel_id] = [
                'process_id' => $dryingProcess->process_id,
                'device_id' => $panel_id,
                'timestamp' => now(),
                'kadar_air_gabah' => isset($data['grain_moisture']) ? (float) $data['grain_moisture'] : null,
                'suhu_gabah' => isset($data['grain_temperature']) ? (float) $data['grain_temperature'] : null,
                'suhu_ruangan' => isset($data['room_temperature']) ? (float) $data['room_temperature'] : null,
                'suhu_pembakaran' => isset($data['burning_temperature']) ? (float) $data['burning_temperature'] : null,
                'status_pengaduk' => isset($data['stirrer_status']) ? (bool) $data['stirrer_status'] : null,
            ];
            Log::info('Stored in buffer', ['panel_id' => $panel_id, 'buffer_size' => count($this->sensorDataBuffer)]);

            // Proses data hanya jika semua topik telah mengirim data
            if (count($this->sensorDataBuffer) === $this->topics->count()) {
                Log::info('Buffer complete, processing data', ['buffer' => $this->sensorDataBuffer]);
                $this->processAndSendData($dryingProcess);
            }
        } catch (\Exception $e) {
            Log::error('Error processing MQTT message: ' . $e->getMessage());
        }
    }

    protected function processAndSendData($dryingProcess)
    {
        try {
            // Simpan semua data sensor ke database
            foreach ($this->sensorDataBuffer as $data) {
                SensorData::create([
                    'process_id' => $data['process_id'],
                    'device_id' => $data['device_id'],
                    'timestamp' => $data['timestamp'],
                    'kadar_air_gabah' => $data['kadar_air_gabah'],
                    'suhu_gabah' => $data['suhu_gabah'],
                    'suhu_ruangan' => $data['suhu_ruangan'],
                    'suhu_pembakaran' => $data['suhu_pembakaran'],
                    'status_pengaduk' => $data['status_pengaduk'],
                ]);
            }
            Log::info('Sensor data saved', ['process_id' => $dryingProcess->process_id, 'records' => count($this->sensorDataBuffer)]);

            // Periksa apakah DryingProcess memiliki data lengkap sebelum mengirim ke ML
            if (is_null($dryingProcess->grain_type_id) || is_null($dryingProcess->berat_gabah_awal) || is_null($dryingProcess->kadar_air_target)) {
                Log::info('Incomplete drying process data, skipping ML prediction', ['process_id' => $dryingProcess->process_id]);
                $this->sensorDataBuffer = [];
                return;
            }

            $buffer_age = time() - min(array_map(fn($data) => $data['timestamp']->timestamp, $this->sensorDataBuffer));
            if ($buffer_age > 60) {
                Log::warning('Buffer timeout, clearing incomplete data');
                $this->sensorDataBuffer = [];
                return;
            }

            $points = [];
            foreach ($this->sensorDataBuffer as $data) {
                $points[] = [
                    'point_id' => $data['device_id'],
                    'grain_temperature' => $data['suhu_gabah'],
                    'grain_moisture' => $data['kadar_air_gabah'],
                    'room_temperature' => $data['suhu_ruangan'],
                    'burning_temperature' => $data['suhu_pembakaran'],
                    'stirrer_status' => $data['status_pengaduk'],
                ];
            }

            $payload = [
                'process_id' => $dryingProcess->process_id,
                'grain_type_id' => $dryingProcess->grain_type_id,
                'points' => $points,
                'weight' => (float) $dryingProcess->berat_gabah_awal,
                'timestamp' => time()
            ];


            $response = Http::timeout(10)->post('http://192.168.43.142:5000/predict', $payload);
            if ($response->successful()) {
                Log::info('Data sent to prediction service', [
                    'process_id' => $dryingProcess->process_id,
                    'payload' => $payload
                ]);
            } else {
                Log::error('Failed to send data to prediction service', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'payload' => $payload
                ]);
            }

            $this->sensorDataBuffer = [];
        } catch (\Exception $e) {
            Log::error('Error processing and sending data: ' . $e->getMessage());
        }
    }

    public function stop()
    {
        try {
            $this->client->disconnect();
            Log::info('MQTT client disconnected');
        } catch (MqttClientException $e) {
            Log::error('Failed to disconnect MQTT client: ' . $e->getMessage());
        }
    }
}