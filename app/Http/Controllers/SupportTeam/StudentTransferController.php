<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\StudentTransfer;
use App\Services\StudentTransferService;
use App\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
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
            ->with([
                // The transfer inbox is viewed by the receiving school, but the student's
                // tenant scope may still point to the sending school (pending) or have
                // already moved (accepted). We must load the student unscoped so
                // photo/name always render.
                'student' => function ($q) {
                    $q->withoutGlobalScopes()->with([
                        'student_record' => function ($srq) {
                            $srq->withoutGlobalScopes()->with(['my_parent', 'my_class', 'section']);
                        },
                    ]);
                },
                'fromSchool',
                'requestedBy',
            ])
            ->where('to_school_id', $school->id)
            ->latest('id')
            ->paginate(20);

        return view('pages.super_admin.transfers.inbox', compact('transfers', 'school'));
    }

    public function outbox()
    {
        $school = $this->currentSchool();

        $transfers = StudentTransfer::query()
            ->with([
                // The outbox is viewed by the sending school, but the student may already
                // have moved to the receiving school (accepted). Load unscoped so the
                // student's photo always renders.
                'student' => function ($q) {
                    $q->withoutGlobalScopes()->with([
                        'student_record' => function ($srq) {
                            $srq->withoutGlobalScopes()->with(['my_parent', 'my_class', 'section']);
                        },
                    ]);
                },
                'toSchool',
                'requestedBy',
                'acceptedBy',
            ])
            ->where('from_school_id', $school->id)
            ->latest('id')
            ->paginate(20);

        return view('pages.super_admin.transfers.outbox', compact('transfers', 'school'));
    }

    public function show(StudentTransfer $transfer)
    {
        $school = $this->currentSchool();

        $allowed = (int) $school->id === (int) $transfer->from_school_id
            || (int) $school->id === (int) $transfer->to_school_id;

        if (!$allowed) {
            abort(403, __('msg.denied'));
        }

        $data = $this->transferService->buildTransferDetails($transfer, auth()->user());
        $data['school'] = $school;

        return view('pages.super_admin.transfers.show', $data);
    }

    public function exportAudit(Request $request): StreamedResponse
    {
        $school = $this->currentSchool();

        $scope = (string) $request->query('scope', 'inbox');
        if (!in_array($scope, ['inbox', 'outbox'], true)) {
            $scope = 'inbox';
        }

        $status = trim((string) $request->query('status', ''));
        $allowedStatuses = [
            StudentTransfer::STATUS_PENDING,
            StudentTransfer::STATUS_ACCEPTED,
            StudentTransfer::STATUS_REJECTED,
            StudentTransfer::STATUS_CANCELLED,
        ];

        $query = StudentTransfer::query()
            ->with([
                'student.student_record.my_parent',
                'student.student_record.my_class',
                'student.student_record.section',
                'fromSchool',
                'toSchool',
                'requestedBy',
                'acceptedBy',
            ])
            ->when($scope === 'inbox', function ($q) use ($school) {
                $q->where('to_school_id', (int) $school->id);
            })
            ->when($scope === 'outbox', function ($q) use ($school) {
                $q->where('from_school_id', (int) $school->id);
            });

        if (in_array($status, $allowedStatuses, true)) {
            $query->where('status', $status);
        }

        $rows = $query->latest('id')->get();

        $filename = 'student-transfer-audit-' . $scope . '-' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($rows, $scope): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'transfer_id',
                'scope',
                'status',
                'student_name',
                'student_code',
                'parent_name',
                'class',
                'section',
                'from_school',
                'to_school',
                'requested_by',
                'accepted_by',
                'requested_at',
                'transferred_at',
                'rejected_reason',
                'transfer_note',
                'last_event',
                'last_event_at',
            ]);

            foreach ($rows as $transfer) {
                $snapshot = is_array($transfer->transfer_snapshot) ? $transfer->transfer_snapshot : [];
                $snapshotAcademic = (array) ($snapshot['academic'] ?? []);

                $student = $transfer->student;
                $record = optional($student)->student_record;

                $history = is_array($transfer->status_history) ? $transfer->status_history : [];
                $lastEvent = !empty($history) ? end($history) : [];

                fputcsv($handle, [
                    (int) $transfer->id,
                    $scope,
                    (string) $transfer->status,
                    (string) (optional($student)->name ?? ($snapshot['student']['name'] ?? '')),
                    (string) (optional($student)->code ?? ($snapshot['student']['code'] ?? '')),
                    (string) (optional(optional($record)->my_parent)->name ?? ($snapshot['parent']['name'] ?? '')),
                    (string) (optional(optional($record)->my_class)->name ?? ($snapshotAcademic['class_name'] ?? '')),
                    (string) (optional(optional($record)->section)->name ?? ($snapshotAcademic['section_name'] ?? '')),
                    (string) optional($transfer->fromSchool)->name,
                    (string) optional($transfer->toSchool)->name,
                    (string) optional($transfer->requestedBy)->name,
                    (string) optional($transfer->acceptedBy)->name,
                    (string) optional($transfer->created_at)->toDateTimeString(),
                    (string) (optional($transfer->transferred_at)->toDateTimeString() ?: ''),
                    (string) ($transfer->rejected_reason ?? ''),
                    (string) ($transfer->transfer_note ?? ''),
                    (string) ($lastEvent['event'] ?? ''),
                    (string) ($lastEvent['at'] ?? ''),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function accept(Request $request, StudentTransfer $transfer)
    {
        if ((bool) config('transfers.policies.require_acceptance_checklist', true)) {
            $request->validate([
                'acceptance_checklist' => ['required', 'in:1'],
            ], [
                'acceptance_checklist.required' => 'Policy requires checklist completion before acceptance.',
                'acceptance_checklist.in' => 'Policy requires checklist completion before acceptance.',
            ]);
        }

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
