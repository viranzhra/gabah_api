<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class GrainType extends Model
{
    protected $table = 'grain_types';
    protected $primaryKey = 'grain_type_id';

    protected $fillable = ['user_id', 'nama_jenis', 'deskripsi'];

    public $timestamps = true;

    public function dryingProcesses()
    {
        return $this->hasMany(DryingProcess::class, 'grain_type_id');
    }

    /**
     * Scope a query to only include grain types of the authenticated user.
     */
    public function scopeForUser($query)
    {
        return $query->where('user_id', Auth::id());
    }
}