<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SensorData extends Model
{
    protected $table = 'sensor_data';
    protected $primaryKey = 'sensor_id';

    protected $fillable = [
        'process_id',
        'device_id',
        'timestamp',
        'kadar_air_gabah',
        'suhu_gabah',
        'suhu_ruangan',
        'suhu_pembakaran'
    ];

    public $timestamps = false;

    public function dryingProcess()
    {
        return $this->belongsTo(DryingProcess::class, 'process_id');
    }

    public function sensorDevice()
    {
        return $this->belongsTo(SensorDevice::class, 'device_id', 'device_id');
    }
}
