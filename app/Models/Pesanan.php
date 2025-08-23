<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pesanan extends Model
{
    protected $table = 'pesanan';
    protected $fillable = [
        'user_id',
        'paket_id',
        'alamat',
        'catatan',
        'bukti_pembayaran',
        'nomor_struk',
        'status'
    ];

    public function paketHarga()
    {
        return $this->belongsTo(PaketHarga::class, 'paket_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}