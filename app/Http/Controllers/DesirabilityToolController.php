<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Affiliate;
use Illuminate\Support\Facades\DB;
use App\UndesirableAffiliate;
use App\PriceProgram;
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
        $workout_programs_amount = count(WorkoutProgram::all());

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
            'affiliate_id' => $id
        ]))->make(true);
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
                        WHERE issue_date >= :start_time AND stats_t.site_id <> 813 AND affiliate_id <> 0
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
                $undesirable_affiliate = static::createUndesirableAffiliate($metrics, 0, 0, 1);
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
    public static function createUndesirableAffiliate($metrics, $workout_program_id, $is_informed, $is_active)
    {
        print_r('Create new undesirable affiliate' . "\n");

        UndesirableAffiliate::calculateDesirabilityScore($metrics);
        $data = static::prepareUndesirableAffiliateData($metrics, $workout_program_id, $is_informed, $is_active);

        $data['desirability_scores'] = -3; //todo delete it`s for test
        $data['gross_margin_126'] = 0.333; //todo delete it`s for test

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
                $data = static::prepareUndesirableAffiliateData($metrics, 0, 0, 1);

                $data['desirability_scores'] = -6; //todo delete it`s for test
                $data['gross_margin_126'] = 0.222; //todo delete it`s for test


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
        static::createUndesirableAffiliate($metrics, $affiliate->workout_program_id, 0, 0); //todo select price program related workout program

        if ($affiliate->workout_duration) {
            $history_limit = $affiliate->workout_duration / 30;
        } else {
            $history_limit = 6;
        }

        var_dump($history_limit);

        $ids_to_delete = DB::table('undesirable_affiliates_2')
            ->where('is_active', 0)
            ->where('affiliate_id', $affiliate->affiliate_id)
            ->groupBy('id')
            ->limit($history_limit)
            ->pluck('id')
            ->toArray();

                var_dump($ids_to_delete);

        if (count($ids_to_delete)) {
            DB::table('undesirable_affiliates_2')
                ->where('is_active', 0)
                ->where('affiliate_id', $affiliate->affiliate_id)
                ->whereNotIn('id', $ids_to_delete)
                ->delete();
        }

    }

    /**
     * @param $id
     */
    public static function deleteHistory($id)
    {
        return DB::table('undesirable_affiliates_2')
            ->where('is_active', 0)
            ->where('affiliate_id', $id)
            ->delete();
    }

    /**
     * @param $id
     * @param $workout_program_id
     * @return bool
     */
    public function updateUndesirableAffiliateById($id, $workout_program_id, $is_informed = 0)
    {


        if ($workout_program_id != 0) {
            $workout_program = WorkoutProgram::find($workout_program_id);
            $price_program = PriceProgram::find($workout_program->price_program_id);

            $data = [
                'updated_price_name' => $price_program->price_name,
                'updated_price' => $price_program->price,
                'workout_program_id' => $workout_program->id,
                'workout_duration' => intval($workout_program->duration),
                'workout_set_date' => date("Y-m-d"),
                'program_status' => 'in_program'
            ];
        } else {
            $price_program = PriceProgram::find(1);
            $data = [
                'updated_price_name' => $price_program->price_name,
                'updated_price' => $price_program->price,
                'workout_program_id' => 0,
                'workout_duration' => 0,
                'workout_set_date' => 0,
                'program_status' => 'set_program'
            ];
        }

        $data['email_status'] = ($is_informed == '1') ? 'wp_change' : 'not_sent';

        $undesirable_affiliate = UndesirableAffiliate::find($id)->fill($data);

        static::deleteHistory($undesirable_affiliate->affiliate_id);

        return ($undesirable_affiliate->update()) ? $undesirable_affiliate : false;
    }

    /**
     * @param $metrics
     * @return array
     */
    private static function prepareUndesirableAffiliateData($metrics, $workout_program_id = 0, $is_informed = 0, $is_active)
    {
        $affiliate = Affiliate::find($metrics->affiliate_id);
        $price_program = PriceProgram::find(1); //Regular CPA

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
            'review_date' => date("Y-m-d H:i:s"),
            'aff_price' => $price_program->price,
            'total_sales_126' => $metrics->total_sales_126,
            'total_cost_126' => $metrics->total_cost_126,
            'gross_margin_126' => $metrics->gross_margin_126,
            'num_disputes_126' => $metrics->num_disputes_126,
            'desirability_scores' => $metrics->desirability_scores,
            'updated_price_name' => $price_program->price_name,
            'is_active' => $is_active
        ];

        if (intval($workout_program_id) > 0) {
            $workout_program = WorkoutProgram::find($workout_program_id);
            $price_program = PriceProgram::find($workout_program->price_program_id);

            $data['updated_price_name'] = $price_program->price_name;
            $data['updated_price'] = $price_program->price;
            $data['workout_program_id'] = $workout_program->id;
            $data['workout_duration'] = intval($workout_program->duration);
            $data['workout_set_date'] = date("Y-m-d");
            $data['program_status'] = 'in_program';
        }

        return $data;
    }

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
