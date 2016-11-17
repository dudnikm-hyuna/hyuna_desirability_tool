<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use App\Affiliate;
use App\UndesirableAffiliate;
use App\ProgramPrice;
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
        $this->info('Cron start');

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
                $this->info('Undesirable affiliate is exist and should be updated: id = ' . $metrics->affiliate_id);
                if (!$affiliate = Affiliate::find($metrics->affiliate_id)) { //todo delete: check that affiliaite is exist in jomedia2 staging
                    continue;
                }

                UndesirableAffiliate::updateByMetrics($undesirable_affiliate_rows_data, $metrics);
            } else {
                $this->info('Undesirable affiliate not exist and should be created: id = ' . $metrics->affiliate_id);

                if (!$affiliate = Affiliate::find($metrics->affiliate_id)) { //todo delete: check that affiliaite is exist in jomedia2 staging
                    continue;
                }

                if (UndesirableAffiliate::calculateDesirabilityScore($metrics) > 0 && $metrics->total_cost >= 1500) { //todo change to total_cost<150
                    continue;
                }

                $undesirable_affiliate = UndesirableAffiliate::createByMetrics($metrics, 1);
            }
        }

        $this->info('Cron successfully done!');
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
