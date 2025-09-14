<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('dryer_id')->nullable()->index();
            $table->unsignedBigInteger('process_id')->nullable()->index();
            $table->string('type')->nullable(); // 'target_moisture_reached' | 'eta_15' | 'eta_5'
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable();
            $table->timestamps();

            // Jika ingin FK (opsional, sesuaikan nama tabel Anda)
            // $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('app_notifications');
    }
};