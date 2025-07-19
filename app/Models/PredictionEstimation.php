<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PredictionEstimation extends Model
{
    protected $table = 'prediction_estimations';
    protected $fillable = ['process_id', 'estimasi_durasi', 'timestamp'];
}