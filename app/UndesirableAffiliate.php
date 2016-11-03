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
    protected $table = 'undesirable_affiliates_2';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'affiliate_id', 'aff_first_name', 'aff_last_name', 'aff_email', 'aff_status', 'country_code', 'aff_type', 'aff_size',
        'date_added', 'review_date', 'aff_price', 'total_sales_126', 'total_cost_126', 'gross_margin_126',
        'num_disputes_126', 'desirability_scores', 'workout_program_id', 'updated_price_name', 'updated_price',
        'workout_duration','workout_set_date', 'is_active', 'program_status', 'email_status', 'email_sent_date', 'is_informed', 'notes'
    ];

    /**
     * @param $total_cost
     * @return string
     */
    public static function calculateAffiliateSize($total_cost)
    {
        if ($total_cost >= 100000) {
            return 'L';
        } elseif ($total_cost >= 60000 & $total_cost < 100000) {
            return 'ML';
        } elseif ($total_cost >= 10000 & $total_cost < 60000) {
            return 'M';
        } elseif ($total_cost >= 150 & $total_cost < 10000) {
            return 'S';
        } else {
            return 'micro';
        }
    }

    /**
     * @param $metrics
     * @return float|int
     */
    public static function calculateDesirabilityScore(&$metrics)
    {
        $min_gm = 0.35;
        $aff_gm_126 = static::calculateGrossMargin($metrics);

        $metrics->gross_margin_126 = $aff_gm_126;

        $score = 0;

        // from % to decimal format (e.g. 0.45)
        $aff_gm_126 = $aff_gm_126 / 100;

        if ($aff_gm_126 < 0) {
            $score = -10;
        } elseif ($aff_gm_126 < $min_gm) {
            $score = round((($aff_gm_126 * 10) - ($min_gm * 10)) / $min_gm);
        } elseif ($aff_gm_126 >= $min_gm) {
            $score = round((($aff_gm_126 - $min_gm) * 10) / (1 - $min_gm));
        }

        $score = ($score == 0) ? $min_gm : $score;

        $metrics->desirability_scores = $score;

        return $score;
    }

    /**
     * @param $metrics
     * @return float
     */
    public static function calculateGrossMargin($metrics)
    {
        $processing_cost = static::calculateProcessingCost($metrics->num_transactions, $metrics->num_disputes);
        $net_settlements_total = static::calculateGrossSettlementsTotal(
            $metrics->gross_settlements_total,
            $metrics->disputes_total,
            $metrics->refunds_total
        );

        return ($net_settlements_total - $processing_cost - $metrics->total_cost_126) / ($net_settlements_total - $processing_cost);
    }

    /**
     * @param $num_transactions
     * @param $num_disputes
     * @return mixed
     */
    public static function calculateProcessingCost($num_transactions, $num_disputes)
    {
        $transaction_fee = 0.2;
        $CB_processing_fee = 20.00;

        return $num_transactions * $transaction_fee + $num_disputes * $CB_processing_fee;
    }

    /**
     * @param $gross_settlements_total
     * @param $disputes_total
     * @param $refunds_total
     * @return mixed
     */
    public static function calculateGrossSettlementsTotal($gross_settlements_total, $disputes_total, $refunds_total)
    {
        return $gross_settlements_total - $disputes_total - $refunds_total;
    }
}