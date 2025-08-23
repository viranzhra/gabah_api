<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SensorDevice extends Model
{
    use HasFactory;

    protected $table = 'sensor_devices';
    protected $primaryKey = 'device_id';
    public $incrementing = true;

    // timestamps di kolom ada (created_at, updated_at) tapi diisi DB.
    // Jika ingin Laravel mengelola, set $timestamps = true.
    public $timestamps = false;

    protected $fillable = [
        'dryer_id',
        'device_id',
        'device_name',
        'address',
        'location',
        'status',
        'created_at',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function bedDryer()
    {
        return $this->belongsTo(BedDryer::class, 'dryer_id', 'dryer_id');
    }

    public function sensorData()
    {
        return $this->hasMany(SensorData::class, 'device_id', 'device_id');
    }

    // Hindari update otomatis updated_at oleh Laravel
    public function setUpdatedAt($value)
    {
        return $this;
    }
}
