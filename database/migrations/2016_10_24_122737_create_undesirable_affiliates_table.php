<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUndesirableAffiliatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('undesirable_affiliates', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('affiliate_id');
            $table->string('affiliate_name', 60);
            $table->string('affiliate_status', 20);
            $table->string('country_code', 2);
            $table->string('affiliate_type', 32);
            $table->string('affiliate_size', 5);
            $table->integer('date_added');
            $table->integer('reviwed_date');
            $table->integer('affiliate_price');
            $table->integer('total_sales_126');
            $table->integer('total_cost_126');
            $table->decimal('gross_margin_126');
            $table->integer('num_disputes_126');
            $table->tinyInteger('desirability_score');
            $table->tinyInteger('workout_program_id')->default(null);
            $table->string('updated_price_name', 20);
            $table->integer('updated_price');
            $table->tinyInteger('workout_duration');
            $table->integer('workout_set_date');
            $table->enum('is_active', [0, 1])->default(1);
            $table->enum('in_program', [0, 1])->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('undesirable_affiliates');
    }
}