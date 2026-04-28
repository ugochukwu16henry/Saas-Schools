<?php

namespace App\Http\Controllers\MyParent;

use App\Http\Controllers\Controller;
use App\Models\StudentTransfer;
use App\Repositories\StudentRepo;
use App\Services\StudentQrService;
use Illuminate\Support\Facades\Auth;

class MyController extends Controller
{
    protected $student;

    public function __construct(StudentRepo $student)
    {
        $this->student = $student;
    }

    public function children()
    {
        $students = $this->student->getRecord(['my_parent_id' => Auth::user()->id])->with(['my_class', 'section', 'user.school'])->get();

        $studentUserIds = $students->pluck('user_id')->map(fn($id) => (int) $id)->all();

        $transfers = StudentTransfer::query()
            ->with(['fromSchool:id,name', 'toSchool:id,name'])
            ->whereIn('student_id', $studentUserIds)
            ->where('status', StudentTransfer::STATUS_ACCEPTED)
            ->latest('id')
            ->get()
            ->groupBy('student_id');

        $qrService = app(StudentQrService::class);
        $qrTokens = [];
        foreach ($students as $studentRecord) {
            if ($studentRecord->user) {
                $qrTokens[(int) $studentRecord->user->id] = $qrService->ensureTokenForStudent($studentRecord->user)->token;
            }
        }

        $data['students'] = $students;
        $data['transferHistoryByStudent'] = $transfers;
        $data['qrTokensByStudent'] = $qrTokens;

        return view('pages.parent.children', $data);
    }
}
