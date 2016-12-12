<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

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
    {   if (!Auth::user()->isManager()) {
            return view('not_allowed');
        }

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
        $grouping = "affiliate_id";
        $extra_where = "AND affiliate_id IN (" . $affiliate_ids . ")";

        $query = UndesirableAffiliate::prepareQuery($grouping, $extra_where);

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

    public function getStatsByCountry($affiliate_id)
    {
        $grouping = "country_code";
        $extra_where = "AND affiliate_id = " . $affiliate_id;
        $order_by = " ORDER BY num_transactions DESC LIMIT 6";

        $query = UndesirableAffiliate::prepareQuery($grouping, $extra_where, $order_by);

        $affiliate_country_data = DB::connection('redshift_prod')->select($query);

        foreach ($affiliate_country_data as $key => $metrics) {
            if (!$metrics->num_transactions_126) {
                unset($affiliate_country_data[$key]);
                continue;
            }

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
