<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingData extends Model
{
    protected $table = 'training_data';
    protected $primaryKey = 'training_id';

    protected $fillable = [
        'process_id',
        'kadar_air_awal',
        'suhu_gabah_awal',
        'suhu_ruangan_awal',
        'berat_gabah_awal',
        'suhu_gabah_akhir',
        'suhu_ruangan_akhir',
        'berat_gabah_akhir',
        'kadar_air_akhir',
        'durasi_nyata',
        'tanggal_awal',
        'tanggal_akhir'
    ];

    public $timestamps = false;

    public function dryingProcess()
    {
        return $this->belongsTo(DryingProcess::class, 'process_id');
    }
}
