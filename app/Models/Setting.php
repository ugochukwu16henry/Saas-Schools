<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Eloquent;

class Setting extends Eloquent
{
    use BelongsToSchool;
    protected $fillable = ['type', 'description'];
}
