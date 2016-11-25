<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AffiliateProgram extends Model
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
    protected $table = 'affiliate_programs';
}
