<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DryerProcess extends Model
{
    protected $table = 'drying_process';
    protected $primaryKey = 'process_id';
    public $incrementing = true;
    protected $keyType = 'int';
    
    protected $fillable = [
        'dryer_id',
        'grain_type_id',
        'timestamp_mulai',
        'timestamp_selesai',
        'berat_gabah_awal',
        'berat_gabah_akhir',
        'kadar_air_awal',
        'kadar_air_target',
        'kadar_air_akhir',
        'durasi_rekomendasi',
        'durasi_terlaksana',
        'avg_estimasi_durasi',
        'status',
        'catatan'
    ];

    protected $casts = [
        'process_id' => 'integer',
        'dryer_id' => 'integer',
        'grain_type_id' => 'integer',
        'timestamp_mulai' => 'datetime',
        'timestamp_selesai' => 'datetime',
        'berat_gabah_awal' => 'decimal:2',
        'berat_gabah_akhir' => 'decimal:2',
        'kadar_air_awal' => 'decimal:2',
        'kadar_air_target' => 'decimal:2',
        'kadar_air_akhir' => 'decimal:2',
        'durasi_rekomendasi' => 'integer',
        'durasi_terlaksana' => 'integer',
        'avg_estimasi_durasi' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Accessor untuk memastikan durasi selalu integer
    public function getDurasiTerlaksanaAttribute($value)
    {
        return (int) $value;
    }

    public function setDurasiTerlaksanaAttribute($value)
    {
        $this->attributes['durasi_terlaksana'] = (int) $value;
    }

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