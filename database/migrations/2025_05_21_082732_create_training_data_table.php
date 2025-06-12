<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('training_data', function (Blueprint $table) {
            $table->id('training_id');
            $table->foreignId('process_id')->constrained('drying_process', 'process_id');
            $table->float('kadar_air_awal');
            $table->float('suhu_gabah_awal');
            $table->float('suhu_ruangan_awal');
            $table->float('berat_gabah_awal');
            $table->float('suhu_gabah_akhir');
            $table->float('suhu_ruangan_akhir');
            $table->float('berat_gabah_akhir');
            $table->float('kadar_air_akhir');
            $table->integer('durasi_nyata');
            $table->dateTime('tanggal_awal');
            $table->dateTime('tanggal_akhir');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_data');
    }
};
