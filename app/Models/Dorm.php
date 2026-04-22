<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Eloquent;

class Dorm extends Eloquent
{
    use BelongsToSchool;
    protected $fillable = ['name', 'description'];
}
