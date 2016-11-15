<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Affiliate;
use Illuminate\Support\Facades\DB;

use App\UndesirableAffiliate;
use App\ProgramPrice;
use App\WorkoutProgram;

use Yajra\Datatables\Facades\Datatables;

class DesirabilityToolController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function tool()
    {
        return view('tool');
    }

    /**
     * @return mixed
     */
    public function getUndesirableAffiliatesData()
    {
        return Datatables::eloquent(UndesirableAffiliate::where(['is_active' => '1']))->make(true);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getUndesirableAffiliateHistoryData($id)
    {
        return Datatables::eloquent(UndesirableAffiliate::where([
            'is_active' => '0',
            'is_history_log' => 0,
            'affiliate_id' => $id
        ]))->make(true);
    }

    public function getUndesirableAffiliateHistoryLogData($affiliate_id)
    {
        $affiliate = UndesirableAffiliate::where([
            'affiliate_id' => $affiliate_id
        ])->first();

        $affiliate_history_log_data = UndesirableAffiliate::where([
            'affiliate_id' => $affiliate_id,
            'is_active' => '0'
        ])->get();

        return view('history_log', [
            'affiliate' => $affiliate,
            'affiliate_history_log_data' => $affiliate_history_log_data
        ]);
    }


    /**
     *
     */
    public function index()
    {
//        $this->info('Fetching affiliates IDs registered more then 126 ago');

        $affiliate_ids = Affiliate::findAffiliatesIdForReview();

        $query = "SELECT    affiliate_id,
                            SUM(stats.num_transactions) AS num_transactions,
                            SUM(stats.gross_settlements_total) AS gross_settlements_total,
                            SUM(stats.disputes_total) AS disputes_total,
                            SUM(stats.refunds_total) AS refunds_total,
                            SUM(stats.total_cost) AS total_cost,
                            SUM(stats.num_disputes) AS num_disputes,
                            SUM(stats.num_disputes_126) AS num_disputes_126,
                            SUM(stats.total_cost_126) AS total_cost_126,
                            SUM(stats.total_sales_126) AS total_sales_126
                FROM jomedia.members m
                JOIN (
                      SELECT
                            stats_m.member_id as member_id,
                            COUNT(CASE WHEN transaction_type NOT IN ('auth', 'void') THEN transaction_id END) AS num_transactions,
                            SUM(CASE WHEN (transaction_type IN ('sale', 'capture') AND ( status = 'success' OR status = 'refunded' ) AND payout_amount > 0 ) THEN transaction_amount ELSE 0 END) AS gross_settlements_total,
                            SUM(CASE WHEN dispute_type = 'chargeback' THEN dispute_amount ELSE 0 END) AS disputes_total,
                            SUM(CASE WHEN (transaction_type = 'refund') THEN ABS(transaction_amount) ELSE 0 END) AS refunds_total,
                            SUM(DISTINCT CASE WHEN payout_amount > 0 THEN payout_amount ELSE 0 END) AS total_cost,
                            SUM(CASE WHEN (transaction_type = 'refund') THEN 1 ELSE 0 END) AS num_refunds, SUM(CASE WHEN dispute_type = 'chargeback' THEN 1 ELSE 0 END) AS num_disputes,
                            SUM(CASE WHEN (dispute_type = 'chargeback' AND (start_date <= EXTRACT(EPOCH FROM SYSDATE)::INT - :days_126) AND (dispute_date - issue_date <= :days_126)) THEN 1 ELSE 0 END) AS num_disputes_126,
                            SUM(DISTINCT CASE WHEN ( payout_amount > 0 AND start_date <= EXTRACT(EPOCH FROM SYSDATE)::INT - :days_126 ) THEN payout_amount ELSE 0 END) AS total_cost_126,
                            COUNT(DISTINCT CASE WHEN ( start_date <= EXTRACT(EPOCH FROM SYSDATE)::INT - :days_126 AND payout_amount > 0 ) THEN stats_m.member_id END) AS total_sales_126
                      FROM  jomedia.members stats_m
                      JOIN jomedia.transactions stats_t ON stats_t.member_id = stats_m.member_id
                        WHERE issue_date >= :start_time
                        AND stats_t.site_id <> 813
                        AND affiliate_id <> 0
                        AND affiliate_id IN(" . $affiliate_ids . ")
                      GROUP BY stats_m.member_id
                ) stats ON m.member_id = stats.member_id
                WHERE  gross_settlements_total > 0 AND total_cost_126 > 0
                GROUP BY affiliate_id"; //todo set appropriate filters and params

        $params = array(
            ":start_time" => strtotime("-1156 days"), //todo change to  -126 or -156
            ":days_126" => 126 * 86400,
        );

        $query = static::prepareQuery($query, $params);

        $affiliates_initial_metrics = DB::connection('redshift')->select($query);

        foreach ($affiliates_initial_metrics as $metrics) {
            $undesirable_affiliate_rows_data = UndesirableAffiliate::where('affiliate_id', $metrics->affiliate_id)->get();

            if (count($undesirable_affiliate_rows_data)) {
                print_r('Undesirable affiliate is exist and should be updated: id' . $metrics->affiliate_id . "\n");
                if (!$affiliate = Affiliate::find($metrics->affiliate_id)) { //todo delete: check that affiliaite is exist in jomedia2 staging
                    continue;
                }

                static::updateUndesirableAffiliate($undesirable_affiliate_rows_data, $metrics);
            } else {
                if (!$affiliate = Affiliate::find($metrics->affiliate_id)) { //todo delete: check that affiliaite is exist in jomedia2 staging
                    continue;
                }

                if (UndesirableAffiliate::calculateDesirabilityScore($metrics) > 0 && $metrics->total_cost >= 1500) { //todo change to total_cost<150
                    continue;
                }

                print_r('Undesirable affiliate not exist and should be created: id' . $metrics->affiliate_id . "\n");
                $undesirable_affiliate = static::createUndesirableAffiliate($metrics, 1);
            }
        }

        var_dump('Cron successfully done!');
        die();
    }

    /**
     * @param $metrics
     * @param $workout_program_id
     * @return static
     */
    public static function createUndesirableAffiliate($metrics, $is_active)
    {
        UndesirableAffiliate::calculateDesirabilityScore($metrics);
        $data = static::prepareUndesirableAffiliateData($metrics, $is_active);

//        $data['desirability_scores'] = -3; //todo delete it`s for test
//        $data['gross_margin_126'] = 0.333; //todo delete it`s for test

        return UndesirableAffiliate::create($data);
    }

    /**
     * @param $affiliate
     * @param $metrics
     * @return bool
     */
    public static function updateUndesirableAffiliate($affiliate_rows_data, $metrics)
    {
        foreach ($affiliate_rows_data as $affiliate_row_data) {
            if (($affiliate_row_data->workout_program_id > 0 &&
                $affiliate_row_data->is_active == '1')
            ) { //todo logic
                static::saveHistory($affiliate_row_data, $metrics);
            } elseif (($affiliate_row_data->workout_program_id == 0 &&
                $affiliate_row_data->is_active == '1')
            ) {
                print_r('Undesirable affiliate' . $metrics->affiliate_id . ' is not in program' . "\n");
                print_r('Update affiliate ' . $metrics->affiliate_id . "\n");

                UndesirableAffiliate::calculateDesirabilityScore($metrics);
                $data = static::prepareUndesirableAffiliateData($metrics, 1);

//                $data['desirability_scores'] = -6; //todo delete it`s for test
//                $data['gross_margin_126'] = 0.222; //todo delete it`s for test


                $undesirable_affiliate = UndesirableAffiliate::find($affiliate_row_data->id)->update($data);
            }
        }
        return true;
    }

    /**
     * @param $affiliate
     * @param $metrics
     */
    public static function saveHistory($affiliate, $metrics)
    {
        static::createUndesirableAffiliate($metrics, 0);

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
            ->groupBy('id')
            ->limit($history_limit)
            ->pluck('id')
            ->toArray();

        if (count($ids_to_history_log)) {
            $undesirable_affiliates = UndesirableAffiliate::whereNotIn('id', $ids_to_history_log)
                ->where([
                    'is_active' => 0,
                    'is_history_log' => 0
                ]);
            $undesirable_affiliates->update(['is_history_log' => 1]);
        }

        return true;
    }

    /**
     * @param $id
     */
    public static function updateHistory($affiliate_id)
    {
        return UndesirableAffiliate::where([
            'is_active' => 0,
            'is_history_log' => 0,
            'affiliate_id' => $affiliate_id
        ])->update(['is_history_log' => 1]);
    }

    /**
     * @param $id
     * @param $workout_program_id
     * @param $program_price
     * @return bool
     */
    public function setProgram($id, $workout_program_id, $price_name)
    {
        $undesirable_affiliate = UndesirableAffiliate::find($id);
        $program_price = ProgramPrice::where([
            'price_name' => $price_name,
            'aff_type' => $undesirable_affiliate->aff_type,
        ])->first();

        if ($workout_program_id > 0) {
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
     * @param $metrics
     * @return array
     */
    private static function prepareUndesirableAffiliateData($metrics, $is_active)
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
            'aff_price' => static::setPrice($affiliate->id, $current_program_id, $affiliate->country_code), //Regular CPA
            'total_sales_126' => $metrics->total_sales_126,
            'total_cost_126' => $metrics->total_cost_126,
            'gross_margin_126' => $metrics->gross_margin_126,
            'num_disputes_126' => $metrics->num_disputes_126,
            'desirability_scores' => $metrics->desirability_scores,
            'updated_price_name' => $program_price->price_name,
            'program_price_id' => $program_price->id,
            'is_active' => $is_active,
            'is_history_log' => 0
        ];

        return $data;
    }

    /**
     * @param $affiliate_id
     * @return int
     */
    private static function getCurrentProgramId($affiliate_id)
    {
        $query = "SELECT `program_id`  FROM  `affiliate_programs`  WHERE `affiliate_id` = " . $affiliate_id . " AND use_default_price = 0";

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
    public static function setPrice($affiliate_id, $program_id, $country_code)
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

    /**
     * @param $id
     * @return bool
     */
    public function sendEmail($id)
    {
        $data = [
            'email_sent_date' => date("Y-m-d H:i:s"),
            'email_status' => 'sent',
            'is_informed' => '1',
        ];

        $undesirable_affiliate = UndesirableAffiliate::find($id)->fill($data);

        return ($undesirable_affiliate->update()) ? $undesirable_affiliate : false;
    }

    /**
     * Replace the bindings with their real value for the query.
     *
     * @param string $query The query.
     * @param array $bindings The list of bindings.
     *
     * @return string
     */
    private static function prepareQuery($query, $bindings)
    {
        foreach ($bindings as $name => $value) {
            $query = str_replace($name, $value, $query);
        }
        return $query;
    }
}
