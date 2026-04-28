<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\StudentTransfer;
use App\Services\StudentTransferService;
use App\User;
use Illuminate\Http\Request;
use RuntimeException;

class StudentTransferController extends Controller
{
    private StudentTransferService $transferService;

    public function __construct(StudentTransferService $transferService)
    {
        $this->middleware('super_admin');
        $this->middleware('ability:school.users.manage');
        $this->transferService = $transferService;
    }

    public function create()
    {
        $school = $this->currentSchool();

        $students = User::query()
            ->where('school_id', $school->id)
            ->where('user_type', 'student')
            ->orderBy('name')
            ->limit(300)
            ->get(['id', 'name', 'code', 'email']);

        return view('pages.super_admin.transfers.create', compact('students', 'school'));
    }

    public function searchSchool(Request $request)
    {
        $school = $this->currentSchool();
        $term = trim((string) $request->query('q', ''));

        if ($term === '') {
            return response()->json([]);
        }

        $results = School::query()
            ->where('id', '!=', $school->id)
            ->searchable($term)
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'unique_code', 'email', 'phone']);

        return response()->json($results);
    }

    public function store(Request $request)
    {
        $request->validate([
            'student_id' => ['required', 'integer', 'exists:users,id'],
            'to_school_id' => ['required', 'integer', 'exists:schools,id'],
            'transfer_note' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            $student = User::withoutGlobalScopes()->findOrFail((int) $request->input('student_id'));
            $toSchool = School::query()->findOrFail((int) $request->input('to_school_id'));

            $this->transferService->initiateTransfer(
                $student,
                $toSchool,
                auth()->user(),
                $request->input('transfer_note')
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['transfer' => $e->getMessage()]);
        }

        return redirect()->route('transfers.outbox')->with('flash_success', 'Transfer request created successfully.');
    }

    public function inbox()
    {
        $school = $this->currentSchool();

        $transfers = StudentTransfer::query()
            ->with(['student', 'fromSchool', 'requestedBy'])
            ->where('to_school_id', $school->id)
            ->latest('id')
            ->paginate(20);

        return view('pages.super_admin.transfers.inbox', compact('transfers', 'school'));
    }

    public function outbox()
    {
        $school = $this->currentSchool();

        $transfers = StudentTransfer::query()
            ->with(['student', 'toSchool', 'requestedBy', 'acceptedBy'])
            ->where('from_school_id', $school->id)
            ->latest('id')
            ->paginate(20);

        return view('pages.super_admin.transfers.outbox', compact('transfers', 'school'));
    }

    public function accept(StudentTransfer $transfer)
    {
        try {
            $this->transferService->acceptTransfer($transfer, auth()->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['transfer' => $e->getMessage()]);
        }

        return back()->with('flash_success', 'Transfer accepted successfully.');
    }

    public function reject(Request $request, StudentTransfer $transfer)
    {
        $request->validate([
            'rejected_reason' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $this->transferService->rejectTransfer($transfer, auth()->user(), (string) $request->input('rejected_reason'));
        } catch (RuntimeException $e) {
            return back()->withErrors(['transfer' => $e->getMessage()]);
        }

        return back()->with('flash_success', 'Transfer rejected.');
    }

    public function cancel(StudentTransfer $transfer)
    {
        try {
            $this->transferService->cancelTransfer($transfer, auth()->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['transfer' => $e->getMessage()]);
        }

        return back()->with('flash_success', 'Transfer cancelled.');
    }

    private function currentSchool(): School
    {
        if (app()->bound('currentSchool')) {
            return app('currentSchool');
        }

        $schoolId = (int) (auth()->user()->school_id ?? 0);
        $school = School::query()->find($schoolId);

        if (!$school) {
            abort(403, __('msg.denied'));
        }

        return $school;
    }
}
