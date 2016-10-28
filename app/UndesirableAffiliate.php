<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UndesirableAffiliate extends Model
{
    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'local';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'undesirable_affiliates';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'affiliate_id', 'affiliate_name', 'affiliate_status', 'country_code', 'affiliate_type', 'affiliate_size',
        'date_added', 'review_date', 'affiliate_price', 'total_sales_126', 'total_cost_126', 'gross_margin_126',
        'num_disputes_126', 'desirability_score', 'updated_price_name', 'updated_price','workout_program_id',
        'workout_duration','workout_set_date','in_program'
    ];
}