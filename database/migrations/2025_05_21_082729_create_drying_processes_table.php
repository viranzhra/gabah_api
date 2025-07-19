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
        // Schema::create('drying_process', function (Blueprint $table) {
        //     $table->id('process_id');
        //     $table->foreignId('user_id')->constrained('users', 'id');
        //     $table->foreignId('grain_type_id')->constrained('grain_types', 'grain_type_id');
        //     $table->dateTime('timestamp_mulai');
        //     $table->dateTime('timestamp_selesai')->nullable();
        //     $table->float('berat_gabah');
        //     $table->float('kadar_air_target');
        //     $table->float('kadar_air_akhir')->nullable();
        //     $table->integer('durasi_rekomendasi');
        //     $table->integer('durasi_aktual')->nullable();
        //     $table->integer('durasi_terlaksana')->default(0);
        //     $table->enum('status', ['pending', 'ongoing', 'completed']);
        //     $table->timestamps();
        // });

        Schema::create('drying_process', function (Blueprint $table) {
            $table->increments('process_id');
            $table->string('lokasi', 100)->nullable();
            $table->foreignId('user_id')->constrained('users', 'id');
            $table->unsignedInteger('grain_type_id');
            $table->dateTime('timestamp_mulai')->useCurrent();
            $table->dateTime('timestamp_selesai')->nullable();
            $table->float('berat_gabah_awal')->nullable();
            $table->float('berat_gabah_akhir')->nullable();
            $table->float('kadar_air_awal')->nullable();
            $table->float('kadar_air_target');
            $table->float('kadar_air_akhir')->nullable();
            $table->float('durasi_rekomendasi');
            $table->float('durasi_aktual')->nullable();
            $table->float('durasi_terlaksana')->default(0);
            $table->float('avg_estimasi_durasi')->nullable();
            $table->enum('status', ['pending', 'ongoing', 'completed'])->default('pending');
            $table->text('catatan')->nullable();

            $table->foreign('grain_type_id')->references('grain_type_id')->on('grain_types')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drying_process');
    }
};
