<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SensorDevice extends Model
{
    protected $table = 'sensor_devices';
    protected $primaryKey = 'device_id';

    protected $fillable = ['device_name', 'location', 'device_type', 'created_at'];

    public $timestamps = false;

    public function sensorData()
    {
        return $this->hasMany(SensorData::class, 'device_id');
    }
}
