<?php

namespace App\Http\Controllers;

use App\Helpers\Qs;
use App\Models\StudentTransfer;
use App\Repositories\UserRepo;
use App\Services\StudentQrService;

class HomeController extends Controller
{
    protected UserRepo $user;

    public function __construct(UserRepo $user)
    {
        $this->user = $user;
    }


    public function index()
    {
        return redirect()->route('dashboard');
    }

    public function privacy_policy()
    {
        $data['app_name'] = config('app.name');
        $data['app_url'] = config('app.url');
        $data['contact_phone'] = Qs::getSetting('phone');
        return view('pages.other.privacy_policy', $data);
    }

    public function terms_of_use()
    {
        $data['app_name'] = config('app.name');
        $data['app_url'] = config('app.url');
        $data['contact_phone'] = Qs::getSetting('phone');
        return view('pages.other.terms_of_use', $data);
    }

    public function dashboard()
    {
        $d = [];
        if (Qs::userIsTeamSAT()) {
            $d['users'] = $this->user->getAll();
        }

        if (app()->bound('currentSchool') && Qs::userIsTeamSA()) {
            $school = app('currentSchool')->loadMissing('billingPlan');
            $d['billingContext'] = [
                'plan_name' => optional($school->billingPlan)->name ?: 'Standard',
                'free_limit' => $school->effectiveFreeStudentLimit(),
                'monthly_rate' => $school->effectiveMonthlyRate(),
                'one_time_rate' => $school->effectiveOneTimeAddRate(),
            ];

            $receivedWindow = (string) request()->query('received_window', '7');
            if (!in_array($receivedWindow, ['7', '30', 'all'], true)) {
                $receivedWindow = '7';
            }

            $receivedTransfersQuery = StudentTransfer::query()
                ->with([
                    'student.student_record.my_class',
                    'student.student_record.section',
                    'student.student_record.my_parent',
                    'fromSchool:id,name',
                    'acceptedBy:id,name',
                ])
                ->where('to_school_id', (int) $school->id)
                ->where('status', StudentTransfer::STATUS_ACCEPTED);

            if ($receivedWindow !== 'all') {
                $days = (int) $receivedWindow;
                $cutoff = now()->subDays($days);

                $receivedTransfersQuery->where(function ($query) use ($cutoff) {
                    $query->where(function ($q) use ($cutoff) {
                        $q->whereNotNull('transferred_at')
                            ->where('transferred_at', '>=', $cutoff);
                    })->orWhere(function ($q) use ($cutoff) {
                        $q->whereNull('transferred_at')
                            ->where('updated_at', '>=', $cutoff);
                    });
                });
            }

            $d['recentlyReceivedTransfers'] = $receivedTransfersQuery
                ->latest('transferred_at')
                ->limit(10)
                ->get();
            $d['receivedWindow'] = $receivedWindow;

            $acceptedLast30Days = StudentTransfer::query()
                ->where('to_school_id', (int) $school->id)
                ->where('status', StudentTransfer::STATUS_ACCEPTED)
                ->whereNotNull('transferred_at')
                ->where('transferred_at', '>=', now()->subDays(30))
                ->get(['created_at', 'transferred_at']);

            $avgAcceptanceHours = null;
            if ($acceptedLast30Days->isNotEmpty()) {
                $avgHours = $acceptedLast30Days
                    ->filter(function ($transfer) {
                        return $transfer->created_at && $transfer->transferred_at;
                    })
                    ->map(function ($transfer) {
                        return $transfer->created_at->diffInMinutes($transfer->transferred_at) / 60;
                    })
                    ->avg();

                $avgAcceptanceHours = is_null($avgHours) ? null : round((float) $avgHours, 1);
            }

            $d['transferKpis'] = [
                'pending_incoming' => (int) StudentTransfer::query()
                    ->where('to_school_id', (int) $school->id)
                    ->where('status', StudentTransfer::STATUS_PENDING)
                    ->count(),
                'pending_outgoing' => (int) StudentTransfer::query()
                    ->where('from_school_id', (int) $school->id)
                    ->where('status', StudentTransfer::STATUS_PENDING)
                    ->count(),
                'accepted_last_30' => (int) $acceptedLast30Days->count(),
                'rejected_last_30' => (int) StudentTransfer::query()
                    ->where('to_school_id', (int) $school->id)
                    ->where('status', StudentTransfer::STATUS_REJECTED)
                    ->where('updated_at', '>=', now()->subDays(30))
                    ->count(),
                'avg_acceptance_hours_last_30' => $avgAcceptanceHours,
            ];

            $receivedTransferQrTokens = [];
            $qrService = app(StudentQrService::class);
            foreach (($d['recentlyReceivedTransfers'] ?? collect()) as $transfer) {
                $receivedStudent = $transfer->student;
                if ($receivedStudent) {
                    $receivedTransferQrTokens[(int) $receivedStudent->id] = $qrService->ensureTokenForStudent($receivedStudent)->token;
                }
            }
            $d['receivedTransferQrTokens'] = $receivedTransferQrTokens;
        }

        if (Qs::userIsStudent()) {
            $student = auth()->user();

            $d['studentTransferHistory'] = StudentTransfer::query()
                ->with(['fromSchool:id,name', 'toSchool:id,name'])
                ->where('student_id', (int) $student->id)
                ->where('status', StudentTransfer::STATUS_ACCEPTED)
                ->latest('id')
                ->get();

            $d['studentQrToken'] = app(StudentQrService::class)->ensureTokenForStudent($student)->token;
        }

        return view('pages.support_team.dashboard', $d);
    }
}
