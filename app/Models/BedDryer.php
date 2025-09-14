<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BedDryer extends Model
{
    use HasFactory;

    protected $table = 'bed_dryers';
    protected $primaryKey = 'dryer_id';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'nama',
        'lokasi',
        'warehouse_id',
        'deskripsi',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /** Owner / pemilik dryer */
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'warehouse_id');
    }

    /** Semua device pada dryer ini */
    public function devices()
    {
        return $this->hasMany(SensorDevice::class, 'dryer_id', 'dryer_id');
    }

    /** Semua proses pengeringan pada dryer ini */
    public function processes()
    {
        return $this->hasMany(DryingProcess::class, 'dryer_id', 'dryer_id');
    }

    /** Proses yang sedang berjalan (jika ada) */
    public function ongoingProcess()
    {
        return $this->hasOne(DryingProcess::class, 'dryer_id', 'dryer_id')
            ->where('status', 'ongoing')
            ->latest('timestamp_mulai');
    }

    /** Scope: filter dryer milik user tertentu */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}