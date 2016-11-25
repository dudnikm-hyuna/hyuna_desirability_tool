<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AffiliateProgramCountryOverride extends Model
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
    protected $table = 'affiliate_program_country_overrides';
}
