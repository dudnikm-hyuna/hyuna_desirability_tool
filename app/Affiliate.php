<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Affiliate extends Model
{
    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'staging';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'affiliates';

    public static function findAffiliatesIdForReview()
    {
        $affiliate_ids = Affiliate::where('date_added', '<', strtotime("-126 days"))
            ->whereIn('status', ['active', 'suspended'])
            ->pluck('id')
            ->toArray();

        return implode(",", $affiliate_ids);
    }
}
