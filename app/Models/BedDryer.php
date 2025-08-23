<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BedDryer extends Model
{
    protected $table = 'bed_dryers';
    protected $primaryKey = 'dryer_id';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $fillable = ['warehouse_id','user_id','nama','deskripsi'];

    public function user() { return $this->belongsTo(User::class, 'user_id'); }
    public function warehouse() { return $this->belongsTo(Warehouse::class, 'warehouse_id', 'warehouse_id'); }
    public function processes() { return $this->hasMany(DryingProcess::class, 'dryer_id', 'dryer_id'); }

    // Jika ada tabel devices (tombak) → SESUAIKAN bila beda
    public function devices() { return $this->hasMany(Device::class, 'dryer_id', 'dryer_id'); }

    // Jika ada sensor_data → SESUAIKAN bila beda
    public function sensorData() { return $this->hasMany(SensorData::class, 'dryer_id', 'dryer_id'); }
}