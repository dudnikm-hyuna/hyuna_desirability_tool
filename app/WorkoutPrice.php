<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WorkoutPrice extends Model
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
    protected $table = 'workout_prices';
}
