<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingData extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_group_id',
        'grain_temperature',
        'grain_moisture',
        'room_temperature',
        'weight'
    ];

    public function group()
    {
        return $this->belongsTo(TrainingGroup::class, 'training_group_id');
    }
}