<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Eloquent;

class Exam extends Eloquent
{
    use BelongsToSchool;
    protected $fillable = ['name', 'term', 'year'];
}
