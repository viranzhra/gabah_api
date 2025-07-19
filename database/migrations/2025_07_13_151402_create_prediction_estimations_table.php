<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePredictionEstimationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prediction_estimations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('process_id');
            $table->float('estimasi_durasi');
            $table->timestamp('timestamp');
            $table->timestamps();

            $table->foreign('process_id')->references('process_id')->on('drying_process')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('prediction_estimations');
    }
}