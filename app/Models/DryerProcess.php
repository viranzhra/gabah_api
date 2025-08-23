<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DryerProcess extends Model
{
    protected $table = 'drying_process';
    protected $primaryKey = 'process_id';
    protected $fillable = [
        'lokasi', 'user_id', 'grain_type_id', 'timestamp_mulai', 'timestamp_selesai',
        'berat_gabah_awal', 'berat_gabah_akhir', 'kadar_air_awal', 'kadar_air_target',
        'kadar_air_akhir', 'durasi_rekomendasi', 'durasi_aktual', 'durasi_terlaksana',
        'avg_estimasi_durasi', 'status', 'catatan'
    ];

    public function grainType()
    {
        return $this->belongsTo(GrainType::class, 'grain_type_id', 'grain_type_id');
    }

    public function sensorData()
    {
        return $this->hasMany(SensorData::class, 'process_id', 'process_id');
    }

    public function predictionEstimations()
    {
        return $this->hasMany(PredictionEstimation::class, 'process_id', 'process_id');
    }

    public function dryer() { return $this->belongsTo(BedDryer::class, 'dryer_id', 'dryer_id'); }

    protected $casts = [
        'timestamp_mulai' => 'datetime',
        'timestamp_selesai' => 'datetime',
        'berat_gabah_awal' => 'float',
        'berat_gabah_akhir' => 'float',
        'durasi_aktual' => 'float',
        'durasi_rekomendasi' => 'float',
        'durasi_terlaksana' => 'float',
        'avg_estimasi_durasi' => 'float',
        'kadar_air_awal' => 'float',
        'kadar_air_target' => 'float',
        'kadar_air_akhir' => 'float',
    ];
}