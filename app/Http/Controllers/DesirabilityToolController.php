<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Affiliate;
use App\UndesirableAffiliate;

use App\Mail\AffiliateNotified;
use Illuminate\Support\Facades\Mail;
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
    public function index()
    {
        return view('tool');
    }

    /**
     * @return mixed
     */
    public function getUndesirableAffiliatesData()
    {
        return Datatables::eloquent(UndesirableAffiliate::where(['is_active' => 1]))->make(true);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getUndesirableAffiliateHistoryData($id)
    {
        return Datatables::eloquent(UndesirableAffiliate::where([
            'is_active' => 0,
            'is_history_log' => 0,
            'affiliate_id' => $id
        ]))->make(true);
    }

    /**
     * @param $affiliate_id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function getUndesirableAffiliateHistoryLogData($affiliate_id)
    {
        $affiliate = UndesirableAffiliate::where([
            'affiliate_id' => $affiliate_id
        ])->first();

        $affiliate_history_log_data = UndesirableAffiliate::where([
            'affiliate_id' => $affiliate_id,
            'is_active' => 0
        ])->get();

        return view('history_log', [
            'affiliate' => $affiliate,
            'affiliate_history_log_data' => $affiliate_history_log_data
        ]);
    }

    /**
     * @param $id
     * @param $workout_program_id
     * @param $price_name
     * @return bool
     */
    public function setWorkoutProgram($id, $workout_program_id, $price_name)
    {
        return UndesirableAffiliate::setWorkoutProgram($id, $workout_program_id, $price_name);
    }

    /**
     * @param $id
     * @return bool
     */
    public function sendEmail($id)
    {
        $undesirable_affiliate = UndesirableAffiliate::find($id);
        $message = (new AffiliateNotified($undesirable_affiliate))
            ->onConnection('redis')
            ->onQueue('emails');

        Mail::to($undesirable_affiliate)
            ->queue($message);

        $data = [
            'email_sent_date' => date("Y-m-d H:i:s"),
            'email_status' => 'sent',
            'is_informed' => 1,
        ];
        $undesirable_affiliate->fill($data);

        return ($undesirable_affiliate->update()) ? $undesirable_affiliate : false;
    }

    public function cron()
    {
        $affiliate_ids = Affiliate::findAffiliatesIdForReview();

        $query = "SELECT    affiliate_id,
                            SUM(stats.num_transactions) AS num_transactions,
                            SUM(stats.num_transactions_126) AS num_transactions_126,
                            SUM(stats.total_amount_paid_126) AS total_amount_paid_126,
                            SUM(stats.total_cost) AS total_cost,
                            SUM(stats.num_disputes_126) AS num_disputes_126,
                            SUM(stats.total_cost_126) AS total_cost_126,
                            SUM(stats.total_sales_126) AS total_sales_126
                FROM jomedia.members m
                JOIN (
                      SELECT
                            stats_m.member_id as member_id,
                            COUNT(CASE WHEN transaction_type NOT IN ('auth', 'void') THEN transaction_id END) AS num_transactions,
                            SUM(CASE WHEN ( start_date <= EXTRACT(EPOCH FROM SYSDATE)::INT - :days_126 AND (issue_date - start_date <= :days_126) AND transaction_type NOT IN ('auth', 'void') ) THEN 1 ELSE 0 END) AS num_transactions_126,
                            SUM(CASE WHEN ( start_date <= EXTRACT(EPOCH FROM SYSDATE)::INT - :days_126 AND (issue_date - start_date <= :days_126) AND transaction_type IN ('sale', 'capture') AND stats_t.status = 'success'  AND payout_amount > 0 ) THEN transaction_amount - dispute_amount ELSE 0 END) AS total_amount_paid_126,
                            SUM(DISTINCT CASE WHEN payout_amount > 0 THEN payout_amount ELSE 0 END) AS total_cost,
                            SUM(CASE WHEN (dispute_type = 'chargeback' AND (start_date <= EXTRACT(EPOCH FROM SYSDATE)::INT - :days_126) AND (dispute_date - issue_date <= :days_126)) THEN 1 ELSE 0 END) AS num_disputes_126,
                            SUM(DISTINCT CASE WHEN ( payout_amount > 0 AND start_date <= EXTRACT(EPOCH FROM SYSDATE)::INT - :days_126 ) THEN payout_amount ELSE 0 END) AS total_cost_126,
                            COUNT(DISTINCT CASE WHEN ( start_date <= EXTRACT(EPOCH FROM SYSDATE)::INT - :days_126 AND payout_amount > 0 ) THEN stats_m.member_id END) AS total_sales_126
                      FROM  jomedia.members stats_m
                      JOIN jomedia.transactions stats_t ON stats_t.member_id = stats_m.member_id
                        WHERE issue_date >= :start_time
                        AND stats_t.site_id <> 813
                        AND affiliate_id <> 0
                        AND affiliate_id IN (" . $affiliate_ids . ")
                      GROUP BY stats_m.member_id
                ) stats ON m.member_id = stats.member_id
                WHERE m.start_date BETWEEN :start_time AND :end_time
                AND m.site_id <> 813
                AND affiliate_id <> 0
                GROUP BY affiliate_id"; //todo set appropriate filters and params

        $start_time = Carbon::now(config('app.timezone'))
            ->subDays(156)
            ->hour(0)
            ->minute(0)
            ->second(0)
            ->timestamp;
        $end_time = Carbon::now(config('app.timezone'))
            ->subDays(126)
            ->hour(23)
            ->minute(59)
            ->second(59)
            ->timestamp;

        $params = array(
            ":start_time" => $start_time,
            ":end_time" => $end_time,
            ":days_126" => 126 * 86400,
        );

        $query = static::prepareQuery($query, $params);

        $affiliates_initial_metrics = DB::connection('redshift_prod')->select($query);

        foreach ($affiliates_initial_metrics as $metrics) {
            $undesirable_affiliate_rows_data = UndesirableAffiliate::where('affiliate_id', $metrics->affiliate_id)->get();
            if (count($undesirable_affiliate_rows_data)) {
                print_r('Undesirable affiliate is exist and should be updated: id' . $metrics->affiliate_id . "\n");
                UndesirableAffiliate::updateByMetrics($undesirable_affiliate_rows_data, $metrics);
            } else {
                if (UndesirableAffiliate::calculateDesirabilityScore($metrics) > 0 || $metrics->total_cost < 150) { //todo change to total_cost<150
                    continue;
                }

                print_r('Undesirable affiliate not exist and should be created: id' . $metrics->affiliate_id . "\n");
                UndesirableAffiliate::createByMetrics($metrics, 1);
            }
        }

        var_dump('Cron successfully done!');
        die();
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

    public function getStatsByCountry($affiliate_id)
    {
        $query = "SELECT    country_code,
                            SUM(stats.num_transactions) AS num_transactions,
                            SUM(stats.num_transactions_126) AS num_transactions_126,
                            SUM(stats.total_amount_paid_126) AS total_amount_paid_126,
                            SUM(stats.total_cost) AS total_cost,
                            SUM(stats.num_disputes_126) AS num_disputes_126,
                            SUM(stats.total_cost_126) AS total_cost_126,
                            SUM(stats.total_sales_126) AS total_sales_126
                FROM jomedia.members m
                JOIN (
                      SELECT
                            stats_m.member_id as member_id,
                            COUNT(CASE WHEN transaction_type NOT IN ('auth', 'void') THEN transaction_id END) AS num_transactions,
                            SUM(CASE WHEN ( start_date <= EXTRACT(EPOCH FROM SYSDATE)::INT - :days_126 AND (issue_date - start_date <= :days_126) AND transaction_type NOT IN ('auth', 'void') ) THEN 1 ELSE 0 END) AS num_transactions_126,
                            SUM(CASE WHEN ( start_date <= EXTRACT(EPOCH FROM SYSDATE)::INT - :days_126 AND (issue_date - start_date <= :days_126) AND transaction_type IN ('sale', 'capture') AND stats_t.status = 'success'  AND payout_amount > 0 ) THEN transaction_amount - dispute_amount ELSE 0 END) AS total_amount_paid_126,
                            SUM(DISTINCT CASE WHEN payout_amount > 0 THEN payout_amount ELSE 0 END) AS total_cost,
                            SUM(CASE WHEN (dispute_type = 'chargeback' AND (start_date <= EXTRACT(EPOCH FROM SYSDATE)::INT - :days_126) AND (dispute_date - issue_date <= :days_126)) THEN 1 ELSE 0 END) AS num_disputes_126,
                            SUM(DISTINCT CASE WHEN ( payout_amount > 0 AND start_date <= EXTRACT(EPOCH FROM SYSDATE)::INT - :days_126 ) THEN payout_amount ELSE 0 END) AS total_cost_126,
                            COUNT(DISTINCT CASE WHEN ( start_date <= EXTRACT(EPOCH FROM SYSDATE)::INT - :days_126 AND payout_amount > 0 ) THEN stats_m.member_id END) AS total_sales_126
                      FROM  jomedia.members stats_m
                      JOIN jomedia.transactions stats_t ON stats_t.member_id = stats_m.member_id
                        WHERE issue_date >= :start_time
                        AND stats_t.site_id <> 813
                        AND affiliate_id <> 0
                        AND affiliate_id = :affiliate_id
                      GROUP BY stats_m.member_id
                ) stats ON m.member_id = stats.member_id
                WHERE m.start_date BETWEEN :start_time AND :end_time
                AND m.site_id <> 813
                AND affiliate_id <> 0
                GROUP BY country_code
                ORDER BY num_transactions DESC LIMIT 6"; //todo set appropriate filters and params

        $start_time = Carbon::now(config('app.timezone'))
            ->subDays(156)
            ->hour(0)
            ->minute(0)
            ->second(0)
            ->timestamp;
        $end_time = Carbon::now(config('app.timezone'))
            ->subDays(126)
            ->hour(23)
            ->minute(59)
            ->second(59)
            ->timestamp;

        $params = array(
            ":affiliate_id" => $affiliate_id,
            ":start_time" => $start_time,
            ":end_time" => $end_time,
            ":days_126" => 126 * 86400,
        );

        $query = static::prepareQuery($query, $params);

        $affiliate_country_data = DB::connection('redshift_prod')->select($query);

        foreach ($affiliate_country_data as $key => $metrics) {
            UndesirableAffiliate::calculateDesirabilityScore($metrics);
        }

        $affiliate = UndesirableAffiliate::where([
            'affiliate_id' => $affiliate_id
        ])->first();

        return view('country_data', [
            'affiliate' => $affiliate,
            'affiliate_country_data' => $affiliate_country_data
        ]);
    }

}
