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
        Schema::create('sensor_devices', function (Blueprint $table) {
            $table->id('device_id');
            $table->string('device_name', 50);
            $table->string('location', 100);
            $table->enum('device_type', ['grain_sensor', 'room_sensor']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensor_devices');
    }
};
