<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use App\User;
use Eloquent;

class ExamRecord extends Eloquent
{
    use BelongsToSchool;
    protected $fillable = ['exam_id', 'my_class_id', 'student_id', 'section_id', 'af', 'af_id', 'ps', 'ps_id', 't_comment', 'p_comment', 'year', 'total', 'ave', 'class_ave', 'pos'];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function my_class()
    {
        return $this->belongsTo(MyClass::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
