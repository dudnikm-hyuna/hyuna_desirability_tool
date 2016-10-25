<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WorkoutProgram extends Model
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
    protected $table = 'workout_programs';
}
