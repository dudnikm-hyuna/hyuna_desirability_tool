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
            $table->string('affiliate_name');
            $table->decimal('gm', 5, 3);
            $table->enum('in_program', [0, 1]);
            $table->smallInteger('desirability_score');
            $table->date('date_added');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('undesirable_affiliates');
    }
}