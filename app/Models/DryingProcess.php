<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DryingProcess extends Model
{
    protected $table = 'drying_process';
    protected $primaryKey = 'process_id';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'dryer_id',               // <-- ganti dari user_id ke dryer_id
        'grain_type_id',
        'timestamp_mulai',
        'timestamp_selesai',
        'berat_gabah_awal',
        'berat_gabah_akhir',
        'kadar_air_awal',
        'kadar_air_target',
        'kadar_air_akhir',
        'durasi_rekomendasi',
        'durasi_aktual',
        'durasi_terlaksana',
        'avg_estimasi_durasi',
        'status',
        'catatan',
        // 'lokasi',               // <-- tidak ada lagi di drying_process (lokasi ada di bed_dryers)
    ];

    protected $casts = [
        'timestamp_mulai' => 'datetime',
        'timestamp_selesai' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /** Relasi ke dryer */
    public function bedDryer()
    {
        return $this->belongsTo(BedDryer::class, 'dryer_id', 'dryer_id');
    }

    /** Jenis gabah */
    public function grainType()
    {
        return $this->belongsTo(GrainType::class, 'grain_type_id', 'grain_type_id');
    }

    /** Data sensor per proses */
    public function sensorData()
    {
        return $this->hasMany(SensorData::class, 'process_id', 'process_id');
    }

    /** Estimasi per interval */
    public function predictionEstimations()
    {
        return $this->hasMany(PredictionEstimation::class, 'process_id', 'process_id');
    }
}
