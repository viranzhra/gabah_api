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
        Schema::create('sensor_data', function (Blueprint $table) {
            $table->id('sensor_id');
            $table->foreignId('process_id')->nullable()->constrained('drying_process', 'process_id');
            $table->foreignId('device_id')->constrained('sensor_devices', 'device_id');
            $table->dateTime('timestamp');
            $table->float('kadar_air_gabah')->nullable();
            $table->float('suhu_gabah')->nullable();
            $table->float('suhu_ruangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensor_data');
    }
};
