<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SensorDataUpdated
{
    use Dispatchable, SerializesModels;

    public $sensorData;

    public function __construct($sensorData)
    {
        $this->sensorData = $sensorData;
    }

    public function broadcastOn()
    {
        // Validasi process_id
        if (empty($this->sensorData->process_id)) {
            Log::warning('SensorDataUpdated: process_id kosong atau tidak valid', [
                'sensorData' => $this->sensorData
            ]);
            return []; // Tidak mengirim event jika process_id tidak valid
        }

        return new PrivateChannel('drying-process.' . $this->sensorData->process_id);
    }

    public function broadcastAs()
    {
        return 'sensor-updated';
    }

    public function broadcastWith()
    {
        // Validasi data sensor
        $requiredFields = ['process_id', 'device_id', 'timestamp', 'suhu_pembakaran', 'kadar_air_gabah', 'suhu_gabah', 'suhu_ruangan', 'status_pengaduk'];
        foreach ($requiredFields as $field) {
            if (!isset($this->sensorData->$field) || is_null($this->sensorData->$field)) {
                Log::warning("SensorDataUpdated: Field $field tidak tersedia atau null", [
                    'sensorData' => $this->sensorData
                ]);
                return []; // Kembalikan array kosong untuk mencegah event dikirim
            }
        }

        // Log data yang akan dikirim
        Log::info('Mengirim event SensorDataUpdated', [
            'process_id' => $this->sensorData->process_id,
            'sensorData' => $this->sensorData
        ]);

        return [
            'process_id' => $this->sensorData->process_id,
            'dryer_id' => $this->sensorData->device_id, // Ubah device_id menjadi dryer_id
            'device_name' => $this->sensorData->device_id, // Sertakan device_name untuk kompatibilitas
            'kadar_air_gabah' => (float) $this->sensorData->kadar_air_gabah,
            'suhu_gabah' => (float) $this->sensorData->suhu_gabah,
            'suhu_ruangan' => (float) $this->sensorData->suhu_ruangan,
            'suhu_pembakaran' => (float) $this->sensorData->suhu_pembakaran,
            'timestamp' => $this->sensorData->timestamp,
            'latest_stirrer_status' => $this->sensorData->status_pengaduk // Ubah status_pengaduk menjadi latest_stirrer_status
        ];
    }
}