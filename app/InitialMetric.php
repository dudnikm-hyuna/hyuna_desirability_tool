<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InitialMetric extends Model
{
    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'local';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'initial_metrics';

}