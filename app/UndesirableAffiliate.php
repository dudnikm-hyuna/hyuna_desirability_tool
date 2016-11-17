<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use App\Affiliate;
use App\ProgramPrice;
use App\WorkoutProgram;

class UndesirableAffiliate extends Model
{
    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'main';

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
        'affiliate_id', 'aff_first_name', 'aff_last_name', 'aff_email', 'aff_status', 'country_code', 'aff_type',
        'aff_size', 'date_added', 'reviewed_date', 'aff_price', 'total_sales_126', 'total_cost_126', 'gross_margin_126',
        'num_disputes_126', 'desirability_scores', 'workout_program_id', 'updated_price_name', 'program_price_id',
        'workout_duration', 'workout_set_date', 'is_active', 'program_status', 'email_status', 'email_sent_date',
        'is_informed', 'notes'
    ];

    /**
     * @param $metrics
     * @param $workout_program_id
     * @return static
     */
    public static function createByMetrics($metrics, $is_active)
    {
        static::calculateDesirabilityScore($metrics);
        $data = static::prepareData($metrics, $is_active);

        return UndesirableAffiliate::create($data);
    }

    /**
     * @param $metrics
     * @return array
     */
    private static function prepareData($metrics, $is_active)
    {
        $affiliate = Affiliate::find($metrics->affiliate_id);
        $current_program_id = static::getCurrentProgramId($metrics->affiliate_id);
        $program_price = ProgramPrice::where([
            'aff_type' => $affiliate->affiliate_type,
            'program_id' => $current_program_id,
        ])->first();

        $data = [
            'affiliate_id' => $affiliate->id,
            'aff_first_name' => $affiliate->first_name,
            'aff_last_name' => $affiliate->last_name,
            'aff_email' => $affiliate->email,
            'aff_status' => $affiliate->status,
            'country_code' => $affiliate->country_code,
            'aff_type' => $affiliate->affiliate_type,
            'aff_size' => UndesirableAffiliate::calculateAffiliateSize($metrics->total_cost),
            'date_added' => date("Y-m-d H:i:s", $affiliate->date_added),
            'reviewed_date' => date("Y-m-d H:i:s"),
            'aff_price' => static::setPrice($affiliate->id, $current_program_id, $affiliate->country_code),
            'total_sales_126' => $metrics->total_sales_126,
            'total_cost_126' => $metrics->total_cost_126,
            'gross_margin_126' => $metrics->gross_margin_126,
            'num_disputes_126' => $metrics->num_disputes_126,
            'desirability_scores' => $metrics->desirability_scores,
            'updated_price_name' => $program_price->price_name,
            'program_price_id' => $program_price->id,
            'is_active' => $is_active
        ];

        return $data;
    }

    /**
     * @param $affiliate_rows_data
     * @param $metrics
     * @return bool
     */
    public static function updateByMetrics($affiliate_rows_data, $metrics)
    {
        foreach ($affiliate_rows_data as $affiliate_row_data) {
            if ((isset($affiliate_row_data->workout_program_id) &&
                $affiliate_row_data->is_active == 1)
            ) {
                static::saveHistory($affiliate_row_data, $metrics);
            } elseif ((!isset($affiliate_row_data->workout_program_id) &&
                $affiliate_row_data->is_active == 1)
            ) {
                static::calculateDesirabilityScore($metrics);
                $data = static::prepareData($metrics, 1);
                $undesirable_affiliate = UndesirableAffiliate::find($affiliate_row_data->id)->update($data);
            }
        }
        return true;
    }

    /**
     * @param $id
     * @param $workout_program_id
     * @param $price_name
     * @return bool
     */
    public static function setWorkoutProgram($id, $workout_program_id, $price_name)
    {
        $undesirable_affiliate = UndesirableAffiliate::find($id);
        $program_price = ProgramPrice::where([
            'price_name' => $price_name,
            'aff_type' => $undesirable_affiliate->aff_type,
        ])->first();

        if (isset($workout_program_id)) {
            $workout_program = WorkoutProgram::find($workout_program_id);
            $data = [
                'aff_price' => static::setPrice(
                    $undesirable_affiliate->affiliate_id,
                    $program_price->program_id,
                    $undesirable_affiliate->country_code
                ),
                'updated_price_name' => $price_name,
                'workout_program_id' => $workout_program->id,
                'workout_duration' => intval($workout_program->duration),
                'workout_set_date' => date("Y-m-d"),
                'program_status' => 1,
                'email_status' => ($undesirable_affiliate->is_informed == 0) ? 'not_sent' : 'sent'
            ];
        }

        $undesirable_affiliate->fill($data);

        static::updateHistory($undesirable_affiliate->affiliate_id);

        return ($undesirable_affiliate->update()) ? $undesirable_affiliate : false;
    }

    /**
     * @param $affiliate_id
     * @return int
     */
    private static function getCurrentProgramId($affiliate_id)
    {
        $query = "SELECT `program_id`  FROM  `affiliate_programs`
                  WHERE `affiliate_id` = " . $affiliate_id . "
                  AND use_default_price = 0";

        if ($program_id = DB::connection('staging')->select($query)) { //todo ask about
            return $program_id;
        } else {
            return 241;
        }
    }

    /**
     * @param $affiliate_id
     * @param $program_id
     * @param $country_code
     * @return int
     */
    private static function setPrice($affiliate_id, $program_id, $country_code)
    {
        $query = "SELECT payout_amount
                  FROM affiliate_program_country_overrides
                  WHERE affiliate_id=" . $affiliate_id . "
                  AND program_id=" . $program_id . "
                  AND country_code='" . $country_code . "'
                  ";
        $price = DB::connection('staging')->select($query);
        if ($price) {
            return $price[0]->payout_amount;
        }

        $query = "SELECT affiliate_price
                  FROM affiliate_programs
                  WHERE affiliate_id=" . $affiliate_id . "
                  AND program_id=" . $program_id . "
                  AND use_default_price=0
                  ";
        $price = DB::connection('staging')->select($query);
        if ($price) {
            return $price[0]->affiliate_price;
        }

        $query = "SELECT payout_amount
                  FROM program_country_overrides
                  WHERE program_id=" . $program_id . "
                  AND country_code='" . $country_code . "'
                  ";
        $price = DB::connection('staging')->select($query);
        if ($price) {
            return $price[0]->payout_amount;
        }


        $query = "SELECT payout_amount
                  FROM programs
                  WHERE id=" . $program_id . "
                  ";
        $price = DB::connection('staging')->select($query);
        if ($price) {
            return $price[0]->payout_amount;
        }

        return 0;
    }


    private static function prepareHistoryData($metrics, $affiliate, $is_active)
    {
        $data = [
            'affiliate_id' => $affiliate->id,
            'aff_first_name' => $affiliate->first_name,
            'aff_last_name' => $affiliate->last_name,
            'aff_email' => $affiliate->email,
            'aff_status' => $affiliate->status,
            'country_code' => $affiliate->country_code,
            'aff_type' => $affiliate->affiliate_type,
            'aff_size' => static::calculateAffiliateSize($metrics->total_cost),
            'date_added' => date("Y-m-d H:i:s", $affiliate->date_added),
            'reviewed_date' => $affiliate->reviewed_date,
            'aff_price' => $affiliate->aff_price,
            'total_sales_126' => $metrics->total_sales_126,
            'total_cost_126' => $metrics->total_cost_126,
            'gross_margin_126' => $metrics->gross_margin_126,
            'num_disputes_126' => $metrics->num_disputes_126,
            'desirability_scores' => $metrics->desirability_scores,
            'updated_price_name' => $affiliate->updated_price_name,
            'program_price_id' => $affiliate->program_price_id,
            'is_active' => $is_active
        ];

        return $data;
    }

    /**
     * @param $affiliate
     * @param $metrics
     * @return bool
     */
    private static function saveHistory(UndesirableAffiliate $affiliate, $metrics)
    {
        static::calculateDesirabilityScore($metrics);

        $history_data = $affiliate->replicate();
        $history_data->aff_size = static::calculateAffiliateSize($metrics->total_cost);
        $history_data->total_sales_126 = $metrics->total_sales_126;
        $history_data->total_cost_126 = $metrics->total_cost_126;
        $history_data->gross_margin_126 = $metrics->gross_margin_126;
        $history_data->num_disputes_126 = $metrics->num_disputes_126;
        $history_data->desirability_scores = $metrics->desirability_scores;
        $history_data->is_active = 0;
        $history_data->save();

        if ($affiliate->workout_duration) {
            $history_limit = $affiliate->workout_duration / 30;
        } else {
            $history_limit = 6;
        }

        $ids_to_history_log = UndesirableAffiliate::where([
            'is_active' => 0,
            'is_history_log' => 0,
            'affiliate_id' => $affiliate->affiliate_id
        ])
            ->orderBy('id', 'desc')
            ->limit($history_limit)
            ->pluck('id')
            ->toArray();

        if (count($ids_to_history_log)) {
            $undesirable_affiliates = UndesirableAffiliate::where([
                'affiliate_id' => $affiliate->affiliate_id,
                'is_active' => 0,
                'is_history_log' => 0
            ])
                ->whereNotIn('id', $ids_to_history_log)
                ->update(['is_history_log' => 1]);
        }

        return true;
    }

    /**
     * @param $affiliate_id
     */
    private static function updateHistory($affiliate_id)
    {
        return UndesirableAffiliate::where([
            'is_active' => 0,
            'is_history_log' => 0,
            'affiliate_id' => $affiliate_id
        ])->update(['is_history_log' => 1]);
    }

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

        return ($net_settlements_total - $processing_cost - $metrics->total_cost_126) /
        ($net_settlements_total - $processing_cost);
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