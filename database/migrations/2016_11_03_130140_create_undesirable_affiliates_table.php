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
            $table->string('aff_first_name', 25);
            $table->string('aff_last_name', 25);
            $table->string('aff_email', 40);
            $table->string('aff_status', 25);
            $table->string('country_code', 2);
            $table->string('aff_type', 25);
            $table->string('aff_size', 5);
            $table->dateTime('date_added');
            $table->dateTime('review_date');
            $table->integer('aff_price');
            $table->integer('total_sales_126');
            $table->decimal('total_cost_126', 6, 2);
            $table->decimal('gross_margin_126', 4, 2);
            $table->integer('num_disputes_126');
            $table->integer('successful_premium_upgrades');
            $table->integer('desirability_scores');
            $table->tinyInteger('workout_program_id')->default(null);
            $table->string('updated_price_name', 25);
            $table->integer('updated_price');
            $table->integer('workout_duration');
            $table->dateTime('workout_set_date');
            $table->tinyInteger('is_active')->default(1);
            $table->tinyInteger('program_status')->default(0);
            $table->string('email_status', 25)->default('send');
            $table->dateTime('email_sent_date');
            $table->tinyInteger('is_informed')->default(0);
            $table->string('notes');
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
