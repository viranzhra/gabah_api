<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KontakInfo extends Model
{
    protected $table = 'kontak_info';

    protected $fillable = ['alamat', 'telepon', 'email'];
}
