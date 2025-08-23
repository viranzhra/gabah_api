<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PesanUser extends Model
{
    protected $table = 'pesan_user';

    protected $fillable = ['name', 'email', 'message'];
}
