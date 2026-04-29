<?php

namespace App\Http\Controllers;

use App\Helpers\Qs;
use App\Models\TransferNotificationEvent;
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
                    // Keep student photo/name visible even if tenant scope differs.
                    'student' => function ($q) {
                        $q->withoutGlobalScopes()->with([
                            'student_record' => function ($srq) {
                                $srq->withoutGlobalScopes()->with(['my_class', 'section', 'my_parent']);
                            },
                        ]);
                    },
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

            $transferKpiWindow = (string) request()->query('transfer_kpi_window', '30');
            if (!in_array($transferKpiWindow, ['7', '30', '90'], true)) {
                $transferKpiWindow = '30';
            }

            $transferKpiScope = (string) request()->query('transfer_kpi_scope', 'incoming');
            if (!in_array($transferKpiScope, ['incoming', 'outgoing', 'all'], true)) {
                $transferKpiScope = 'incoming';
            }

            $kpiDays = (int) $transferKpiWindow;
            $kpiCutoff = now()->subDays($kpiDays);

            $statusCountsQuery = StudentTransfer::query()
                ->where('created_at', '>=', $kpiCutoff)
                ->when($transferKpiScope === 'incoming', function ($q) use ($school) {
                    $q->where('to_school_id', (int) $school->id);
                })
                ->when($transferKpiScope === 'outgoing', function ($q) use ($school) {
                    $q->where('from_school_id', (int) $school->id);
                });

            $statusCountsRows = (clone $statusCountsQuery)
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->get();

            $statusCounts = [
                StudentTransfer::STATUS_PENDING => 0,
                StudentTransfer::STATUS_ACCEPTED => 0,
                StudentTransfer::STATUS_REJECTED => 0,
                StudentTransfer::STATUS_CANCELLED => 0,
            ];

            foreach ($statusCountsRows as $row) {
                $status = (string) ($row->status ?? '');
                if (array_key_exists($status, $statusCounts)) {
                    $statusCounts[$status] = (int) ($row->total ?? 0);
                }
            }

            $trendRows = (clone $statusCountsQuery)
                ->selectRaw('DATE(created_at) as day, status, COUNT(*) as total')
                ->groupBy('day', 'status')
                ->orderBy('day')
                ->get();

            $trendMap = [];
            foreach ($trendRows as $row) {
                $day = (string) ($row->day ?? '');
                $status = (string) ($row->status ?? '');
                if ($day === '' || !array_key_exists($status, $statusCounts)) {
                    continue;
                }

                if (!isset($trendMap[$day])) {
                    $trendMap[$day] = [
                        StudentTransfer::STATUS_PENDING => 0,
                        StudentTransfer::STATUS_ACCEPTED => 0,
                        StudentTransfer::STATUS_REJECTED => 0,
                        StudentTransfer::STATUS_CANCELLED => 0,
                    ];
                }

                $trendMap[$day][$status] = (int) ($row->total ?? 0);
            }

            ksort($trendMap);

            $notificationFailures = TransferNotificationEvent::query()
                ->where('school_id', (int) $school->id)
                ->where('status', 'failed')
                ->whereNull('resolved_at')
                ->latest('id')
                ->limit(10)
                ->get();

            $d['transferKpiDrilldown'] = [
                'window' => $transferKpiWindow,
                'scope' => $transferKpiScope,
                'status_counts' => $statusCounts,
                'trend' => $trendMap,
            ];

            $d['transferNotificationVisibility'] = [
                'pending_failures_count' => (int) $notificationFailures->count(),
                'recent_failures' => $notificationFailures,
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
