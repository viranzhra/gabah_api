<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     */
    public function up(): void
    {
        Schema::create('pesanan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('paket_id')->constrained('paket_harga')->onDelete('restrict');
            $table->text('alamat');
            $table->text('catatan')->nullable();
            $table->string('bukti_pembayaran');
            $table->string('nomor_struk')->unique();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Batalkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('pesanan');
    }
};