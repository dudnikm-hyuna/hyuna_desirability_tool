<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProgramCountryOverride extends Model
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
    protected $table = 'program_country_overrides';
}
