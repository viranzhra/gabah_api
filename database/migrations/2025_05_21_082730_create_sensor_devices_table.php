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
            $table->increments('device_id');
            $table->unsignedInteger('dryer_id'); // milik dryer tertentu
            $table->string('device_name', 50);
            $table->string('address', 100)->nullable();
            $table->string('location', 100)->nullable();
            $table->boolean('status')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('dryer_id')->references('dryer_id')->on('bed_dryers')->onDelete('cascade');

            // Unik per-bed dryer
            $table->unique(['dryer_id', 'device_name']);
            $table->unique(['dryer_id', 'address']);
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
