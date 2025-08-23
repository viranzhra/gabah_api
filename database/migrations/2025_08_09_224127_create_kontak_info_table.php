<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKontakInfoTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('kontak_info', function (Blueprint $table) {
            $table->id();
            $table->text('alamat');
            $table->string('telepon', 20);
            $table->string('email', 100);
            $table->timestamps(); // created_at & updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('kontak_info');
    }
}
