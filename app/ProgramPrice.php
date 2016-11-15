<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProgramPrice extends Model
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
    protected $table = 'program_prices';
}
