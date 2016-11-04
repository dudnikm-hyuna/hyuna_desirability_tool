<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PriceProgram extends Model
{
    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'main';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'price_programs';
}
