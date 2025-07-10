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
            $table->float('grain_temperature');
            $table->float('grain_moisture');
            $table->float('room_temperature');
            $table->float('combustion_temperature')->nullable();
            $table->float('weight');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_data');
    }
};