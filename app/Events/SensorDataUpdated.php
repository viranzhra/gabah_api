<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SensorDataUpdated implements ShouldBroadcast
{
    use SerializesModels;

    public $dryerId;
    public $payload;

    public function __construct($dryerId, $payload)
    {
        $this->dryerId = $dryerId;
        $this->payload = $payload;
    }

    public function broadcastOn()
    {
        return new Channel('drying-process.' . $this->dryerId);
    }

    public function broadcastAs()
    {
        return 'sensor-updated';
    }

    // private function getSensorDataField($field, $default = null)
    // {
    //     // Tangani jika sensorData adalah array atau objek
    //     if (is_array($this->sensorData) && isset($this->sensorData[$field])) {
    //         return $this->sensorData[$field];
    //     } elseif (is_object($this->sensorData) && isset($this->sensorData->$field)) {
    //         return $this->sensorData->$field;
    //     }
    //     Log::warning("SensorDataUpdated: Field $field tidak tersedia", [
    //         'sensorData' => $this->sensorData
    //     ]);
    //     return $default;
    // }

    // public function broadcastWith()
    // {
    //     $data = [
    //         'process_id' => $this->getSensorDataField('process_id', 'default'),
    //         'dryer_id' => $this->getSensorDataField('device_id', 'unknown'),
    //         'device_name' => $this->getSensorDataField('device_id', 'Unknown Device'),
    //         'kadar_air_gabah' => $this->getSensorDataField('kadar_air_gabah') ? (float) $this->getSensorDataField('kadar_air_gabah') : null,
    //         'suhu_gabah' => $this->getSensorDataField('suhu_gabah') ? (float) $this->getSensorDataField('suhu_gabah') : null,
    //         'suhu_ruangan' => $this->getSensorDataField('suhu_ruangan') ? (float) $this->getSensorDataField('suhu_ruangan') : null,
    //         'suhu_pembakaran' => $this->getSensorDataField('suhu_pembakaran') ? (float) $this->getSensorDataField('suhu_pembakaran') : null,
    //         'timestamp' => $this->getSensorDataField('timestamp', now()->toDateTimeString()),
    //         'latest_stirrer_status' => $this->getSensorDataField('status_pengaduk', 'unknown')
    //     ];

    //     Log::info('Mengirim event SensorDataUpdated', $data);

    //     return $data;
    // }
}