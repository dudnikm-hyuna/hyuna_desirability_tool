<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInitialWorkoutProgramTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('initial_workout_program', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('affiliate_id');
            $table->smallInteger('price');
            $table->smallInteger('total_sale_126');
            $table->smallInteger('total_cost_126');
            $table->smallInteger('gross_margin_126');
            $table->smallInteger('disputes_126');
            $table->decimal('dispute_percent', 5, 2);
            $table->smallInteger('desirability_score');
            $table->tinyInteger('workout_program');
            $table->string('updated_price_plan');
            $table->decimal('updated_price', 5, 2);
            $table->tinyInteger('workout_time_period');
            $table->tinyInteger('active');
            $table->date('wp_set_date');
            $table->string('notes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('initial_workout_program');
    }
}
