<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInitialMetricsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('initial_metrics', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('affiliate_id');
            $table->decimal('total_cost', 5, 2);
            $table->decimal('gross_settlements_total', 5, 2);
            $table->decimal('processing_cost', 5, 2);
            $table->decimal('refunds_total', 5, 2);
            $table->decimal('disputes_total', 5, 2);
            $table->decimal('gross_margin_rate', 5, 2);
            $table->decimal('gross_margin_wo_disputes_rate', 5, 2);
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
        Schema::dropIfExists('initial_metrics');
    }
}