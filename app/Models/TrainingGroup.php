<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingGroup extends Model
{
    use HasFactory;

    protected $fillable = ['process_id', 'drying_time'];

    public function measurements()
    {
        return $this->hasMany(TrainingData::class);
    }

    public function process()
    {
        return $this->belongsTo(DryingProcess::class, 'process_id');
    }
}