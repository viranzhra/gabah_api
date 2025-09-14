<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_data', function (Blueprint $table) {
            $table->increments('training_id');


            $table->decimal('kadar_air_gabah', 10, 7)->nullable();
            $table->decimal('suhu_gabah', 10, 7)->nullable();
            $table->decimal('suhu_ruangan', 10, 7)->nullable();
            $table->decimal('suhu_pembakaran', 10, 7)->nullable();
            $table->boolean('status_pengaduk')->default(false);

            $table->decimal('durasi_aktual', 20, 7)->nullable();           // durasi aktual (menit)
            $table->dateTime('timestamp')->nullable();
            $table->unsignedInteger('group_id')->nullable();

            $table->timestamps();

            $table->foreign('group_id')->references('group_id')->on('training_group')->onDelete('set null');

            $table->index(['timestamp']);
            $table->index(['group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_data');
    }
};