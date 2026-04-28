<?php

namespace App\Services;

use App\Models\ExamRecord;
use App\Models\Mark;
use App\Models\Promotion;
use App\Models\School;
use App\Models\StudentTransfer;
use App\User;

class StudentTranscriptService
{
    public function build(User $student): array
    {
        $studentModel = User::withoutGlobalScopes()
            ->with(['school', 'student_record.my_class', 'student_record.section', 'student_record.my_parent'])
            ->findOrFail($student->id);

        $transfers = StudentTransfer::query()
            ->with(['fromSchool:id,name,logo,email,phone', 'toSchool:id,name,logo,email,phone'])
            ->where('student_id', (int) $studentModel->id)
            ->where('status', StudentTransfer::STATUS_ACCEPTED)
            ->orderBy('transferred_at')
            ->orderBy('id')
            ->get();

        $schoolIds = collect([$studentModel->school_id])
            ->merge($transfers->pluck('from_school_id'))
            ->merge($transfers->pluck('to_school_id'))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $schools = School::query()
            ->whereIn('id', $schoolIds)
            ->get(['id', 'name', 'logo', 'email', 'phone', 'unique_code'])
            ->keyBy('id');

        $marks = Mark::withoutGlobalScopes()
            ->where('student_id', (int) $studentModel->id)
            ->with([
                'subject:id,name',
                'exam:id,name,term,year',
                'my_class:id,name',
                'section:id,name',
                'grade:id,name',
            ])
            ->orderBy('year')
            ->orderBy('exam_id')
            ->orderBy('subject_id')
            ->get();

        $examRecords = ExamRecord::withoutGlobalScopes()
            ->where('student_id', (int) $studentModel->id)
            ->with(['exam:id,name,term,year', 'my_class:id,name', 'section:id,name'])
            ->orderBy('year')
            ->orderBy('exam_id')
            ->get();

        $promotions = Promotion::withoutGlobalScopes()
            ->where('student_id', (int) $studentModel->id)
            ->with(['fc:id,name', 'fs:id,name', 'tc:id,name', 'ts:id,name'])
            ->orderBy('from_session')
            ->orderBy('id')
            ->get();

        return [
            'student' => $studentModel,
            'record' => $studentModel->student_record,
            'schools' => $schools,
            'transfers' => $transfers,
            'marks' => $marks,
            'examRecords' => $examRecords,
            'promotions' => $promotions,
        ];
    }
}
