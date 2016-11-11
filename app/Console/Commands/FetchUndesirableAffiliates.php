<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Affiliate;
use Illuminate\Support\Facades\DB;
use App\UndesirableAffiliate;
use App\PriceProgram;
use App\WorkoutProgram;

class FetchUndesirableAffiliates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'undesirable_affiliates:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch undesirable affiliates';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Fetching affiliates IDs registered more then 126 ago');

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
                        WHERE issue_date >= :start_time AND stats_t.site_id <> 813 AND affiliate_id <> 0 AND affiliate_id IN (" . $affiliate_ids . ") AND affiliate_id = '63935'
                      GROUP BY stats_m.member_id
                ) stats ON m.member_id = stats.member_id
                GROUP BY affiliate_id"; //todo set appropriate filters and params

        $params = array(
            ":start_time" => strtotime("-4336 days"), //todo change to  -126 or -156
            ":days_126" => 126 * 86400,
        );

        $query = static::prepareQuery($query, $params);

        $affiliates_initial_metrics = DB::connection('redshift')->select($query);

        foreach ($affiliates_initial_metrics as $metrics) {
            if ($undesirable_affiliate = UndesirableAffiliate::where('affiliate_id', $metrics->affiliate_id)->first()) {
                print_r('Undesirable affiliate is exist and should be updated: id' . $metrics->affiliate_id . "\n");
                static::updateUndesirableAffiliate($undesirable_affiliate, $metrics);
            } else {
                if (static::calculateDesirabilityScore($metrics) > 0 && $metrics->total_cost >= 1500) { //todo change to total_cost<150
                    continue;
                }
                print_r('Undesirable affiliate not exist and should be created: id' . $metrics->affiliate_id . "\n");
                $undesirable_affiliate = static::createUndesirableAffiliate($metrics);
            }

        }

        $this->info('Cron successfully done!');
    }

    public static function createUndesirableAffiliate($metrics)
    {
        print_r('Create new undesirable affiliate' . "\n");
        $data = static::prepareUndesirableAffiliateData($metrics);
        return UndesirableAffiliate::create($data);
    }

    /**
     * @param $affiliate
     * @param $metrics
     * @return bool
     */
    public static function updateUndesirableAffiliate($affiliate, $metrics) //($affiliate, $workout_program_id, $price_program_id)
    {
        if ($affiliate->workout_program_id == 1 ||
            $affiliate->workout_program_id == 2 &&
            $affiliate->is_active == '1'
        ) {
            print_r('Undesirable affiliate in workout programs 1 and 2: id' . $metrics->affiliate_id . "\n");
            print_r('Save new data in history: id' . "\n");
            $affiliate_to_history = $affiliate->replicate();
            $affiliate_to_history->is_active = '0';
            $affiliate_to_history->save();

        } elseif ($affiliate->is_active == '1' && $affiliate->in_program == '1') { //todo logic
            print_r('Undesirable affiliate is in program: id' . $metrics->affiliate_id . "\n");
            print_r('Save old data in history and create new: id' . "\n");
            $affiliate->is_active = '0';
            $affiliate->save();

            $undesirable_affiliate = static::createUndesirableAffiliate($metrics);
        } elseif ($affiliate->is_active == '1' && $affiliate->in_program == '0') {
            print_r('Undesirable affiliate is not in program: id' . $metrics->affiliate_id . "\n");
            print_r('Update data' . "\n");
            print_r($affiliate->id . "\n");
            print_r($metrics . "\n");die();

            $data = static::prepareUndesirableAffiliateData($metrics);


            $undesirable_affiliate = UndesirableAffiliate::find($affiliate->id)->update($data);
        }

        return true;
    }


    /**
     * @param $metrics
     * @return array
     */
    private static function prepareUndesirableAffiliateData($metrics)
    {
        $affiliate = Affiliate::find($metrics->affiliate_id);
        $price_program = PriceProgram::find(1); //Regular CPA

        $data = [
            'affiliate_id' => $affiliate->id,
            'affiliate_name' => $affiliate->first_name . ' ' . $affiliate->last_name,
            'affiliate_status' => $affiliate->status,
            'country_code' => $affiliate->country_code,
            'affiliate_type' => $affiliate->affiliate_type,
            'affiliate_size' => static::calculateAffiliateSize($metrics->total_cost),
            'date_added' => $affiliate->date_added,
            'reviewed_date' => date("Y-m-d H:i:s"),
            'affiliate_price' => $price_program->price,
            'total_sales_126' => $metrics->total_sales_126,
            'total_cost_126' => $metrics->total_cost_126,
            'gross_margin_126' => $metrics->gross_margin_126,
            'num_disputes_126' => $metrics->num_disputes_126,
            'desirability_score' => $metrics->desirability_score,
            'updated_price_name' => $price_program->price_name,
        ];

        return $data;
    }

    /**
     * @param $total_cost
     * @return string
     */
    private static function calculateAffiliateSize($total_cost)
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
    private static function calculateDesirabilityScore(&$metrics)
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

        $metrics->desirability_score = $score;

        return $score;
    }

    /**
     * @param $metrics
     * @return float
     */
    private static function calculateGrossMargin($metrics)
    {
        $processing_cost = static::calculateProcessingCost($metrics->num_transactions, $metrics->num_disputes);
        $net_settlements_total = static::calculateGrossSettlementsTotal(
            $metrics->gross_settlements_total,
            $metrics->disputes_total,
            $metrics->refunds_total
        );

        return ($net_settlements_total - $processing_cost - $metrics->total_cost_126) / ($net_settlements_total - $processing_cost);
    }

    private static function calculateProcessingCost($num_transactions, $num_disputes)
    {
        $transaction_fee = 0.2;
        $CB_processing_fee = 20.00;

        return $num_transactions * $transaction_fee + $num_disputes * $CB_processing_fee;
    }

    private static function calculateGrossSettlementsTotal($gross_settlements_total, $disputes_total, $refunds_total)
    {
        return $gross_settlements_total - $disputes_total - $refunds_total;
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
