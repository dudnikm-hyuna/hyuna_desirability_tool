<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWorkoutProgramDescriptionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('workout_program_description', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('program_id');
            $table->tinyInteger('program_duration');
            $table->string('updated_price')->nullable();
            $table->tinyInteger('active');
            $table->date('created_at');
            $table->date('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('workout_program_description');
    }
}