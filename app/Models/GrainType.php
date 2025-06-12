<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrainType extends Model
{
    protected $table = 'grain_types';
    protected $primaryKey = 'grain_type_id';

    protected $fillable = ['nama_jenis', 'deskripsi'];

    public $timestamps = false;

    public function dryingProcesses()
    {
        return $this->hasMany(DryingProcess::class, 'grain_type_id');
    }
}
