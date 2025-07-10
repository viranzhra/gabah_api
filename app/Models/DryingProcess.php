<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DryingProcess extends Model
{
    protected $table = 'drying_process';
    protected $primaryKey = 'process_id';
    protected $fillable = [
        'user_id',
        'grain_type_id',
        'timestamp_mulai',
        'timestamp_selesai',
        'berat_gabah',
        'kadar_air_target',
        'kadar_air_awal',
        'kadar_air_akhir',
        'suhu_gabah_awal',
        'suhu_gabah_akhir',
        'suhu_ruangan_awal',
        'suhu_ruangan_akhir',
        'suhu_pembakaran_awal',
        'suhu_pembakaran_akhir',
        'durasi_rekomendasi',
        'durasi_aktual',
        'durasi_terlaksana',
        'status',
    ];

    protected $casts = [
        'timestamp_mulai' => 'datetime',
        'timestamp_selesai' => 'datetime',
        'status' => 'string',
        'durasi_rekomendasi' => 'float', // Tambahkan cast untuk float
        'berat_gabah' => 'float',
        'kadar_air_target' => 'float',
        'kadar_air_awal' => 'float',
        'kadar_air_akhir' => 'float',
        'suhu_gabah_awal' => 'float',
        'suhu_gabah_akhir' => 'float',
        'suhu_ruangan_awal' => 'float',
        'suhu_ruangan_akhir' => 'float',
        'suhu_pembakaran_awal' => 'float',
        'suhu_pembakaran_akhir' => 'float',
    ];

    public function grainType()
    {
        return $this->belongsTo(GrainType::class, 'grain_type_id', 'grain_type_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function sensorData()
    {
        return $this->hasMany(SensorData::class, 'process_id');
    }

    public function trainingData()
    {
        return $this->hasOne(TrainingData::class, 'process_id');
    }
}