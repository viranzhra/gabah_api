<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_group_id')->constrained()->onDelete('cascade');
            $table->foreignId('jenis_gabah_id')->constrained('grain_types', 'grain_type_id')->onDelete('restrict');
            $table->float('kadar_air_gabah');
            $table->float('suhu_gabah');
            $table->float('suhu_ruangan');
            $table->float('suhu_pembakaran')->nullable();
            $table->float('massa_gabah');
            $table->boolean('status_pengaduk');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_data');
    }
};