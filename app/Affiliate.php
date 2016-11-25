<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\AffiliateProgram;

class Affiliate extends Model
{
    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'jomedia2_prod';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'affiliates';

    public static function findAffiliatesIdForReview()
    {
        $ids =  AffiliateProgram::whereIn('program_id', [241, 408, 420])->pluck('affiliate_id')
            ->toArray();

        $ids = array_slice($ids, 0, 200, true); //todo delete(for test)

        $affiliate_ids = Affiliate::where('date_added', '<', strtotime("-126 days"))
            ->whereIn('status', ['active', 'suspended'])
            ->whereIn('id', $ids)
            ->pluck('id')
            ->toArray();

        return implode(",", $affiliate_ids);
    }
}
