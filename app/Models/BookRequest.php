<?php

namespace App;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class BookRequest extends Model
{
    use BelongsToSchool;
}
