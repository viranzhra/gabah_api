<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaketHarga extends Model
{
    protected $table = 'paket_harga';

    protected $fillable = [
        'nama_paket',
        'harga',
    ];

    public function pesanan()
    {
        return $this->hasMany(Pesanan::class);
    }
}