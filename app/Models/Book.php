<?php

namespace App;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use BelongsToSchool;
}
