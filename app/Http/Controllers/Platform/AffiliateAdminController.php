<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AffiliateCommissionLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AffiliateAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:platform');
    }

    public function index(Request $request)
    {
        $q = Affiliate::query()
            ->withCount('schools')
            ->withSum('commissionLedger as total_commission_ngn', 'total_commission_ngn')
            ->withSum(['commissionLedger as mtd_commission_ngn' => function ($ledger) {
                $ledger->where('created_at', '>=', now()->startOfMonth());
            }], 'total_commission_ngn');

        if ($search = trim((string) $request->get('q'))) {
            $q->where(function ($w) use ($search) {
                $w->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%')
                    ->orWhere('code', 'like', '%' . $search . '%');
            });
        }

        if ($status = $request->get('status')) {
            $q->where('status', $status);
        }

        $affiliates = $q->orderByDesc('created_at')->paginate(25)->withQueryString();

        return view('platform.affiliates.index', compact('affiliates'));
    }

    public function show(Affiliate $affiliate)
    {
        $affiliate->loadCount('schools');
        $schools = $affiliate->schools()
            ->withCount(['users as students_count' => function ($query) {
                $query->where('user_type', 'student');
            }])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($school) {
                $school->billable_count = $school->billableStudentCount();

                return $school;
            });

        $ledger = AffiliateCommissionLedger::query()
            ->where('affiliate_id', $affiliate->id)
            ->with('school:id,name')
            ->orderByDesc('created_at')
            ->paginate(30);

        $totalEarned = AffiliateCommissionLedger::query()
            ->where('affiliate_id', $affiliate->id)
            ->sum('total_commission_ngn');

        $mtdEarned = AffiliateCommissionLedger::query()
            ->where('affiliate_id', $affiliate->id)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('total_commission_ngn');

        return view('platform.affiliates.show', compact('affiliate', 'schools', 'ledger', 'totalEarned', 'mtdEarned'));
    }

    public function approve(Request $request, Affiliate $affiliate)
    {
        if ($affiliate->status === 'approved' && $affiliate->code) {
            return redirect()->back()->with('status', 'Affiliate is already approved.');
        }

        $request->validate([
            'admin_notes' => 'nullable|string|max:5000',
        ]);

        DB::transaction(function () use ($affiliate, $request) {
            if (! $affiliate->code) {
                $affiliate->code = $this->generateUniqueReferralCode();
            }
            $affiliate->status = 'approved';
            $affiliate->approved_at = now();
            $affiliate->approved_by = Auth::guard('platform')->id();
            if ($request->filled('admin_notes')) {
                $affiliate->admin_notes = $request->input('admin_notes');
            }
            $affiliate->save();
        });

        return redirect()->route('platform.affiliates.show', $affiliate)->with('status', 'Affiliate approved. Referral code is active.');
    }

    public function suspend(Request $request, Affiliate $affiliate)
    {
        $request->validate([
            'admin_notes' => 'nullable|string|max:5000',
        ]);

        $affiliate->status = 'suspended';
        if ($request->filled('admin_notes')) {
            $affiliate->admin_notes = $request->input('admin_notes');
        }
        $affiliate->save();

        return redirect()->route('platform.affiliates.show', $affiliate)->with('status', 'Affiliate suspended.');
    }

    public function destroy(Affiliate $affiliate)
    {
        $affiliateName = $affiliate->name;
        $retainedSchools = 0;

        DB::transaction(function () use ($affiliate, &$retainedSchools) {
            $retainedSchools = $affiliate->schools()->count();

            // Preserve referred schools while removing affiliate ownership from them.
            $affiliate->schools()->update(['affiliate_id' => null]);

            $affiliate->delete();
        });

        return redirect()->route('platform.affiliates.index')->with(
            'status',
            "Affiliate {$affiliateName} deleted. {$retainedSchools} referred school(s) retained."
        );
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $filename = 'affiliates-' . now()->format('Y-m-d-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $query = Affiliate::query()
            ->withCount('schools')
            ->withSum('commissionLedger as total_commission_ngn', 'total_commission_ngn')
            ->withSum(['commissionLedger as mtd_commission_ngn' => function ($ledger) {
                $ledger->where('created_at', '>=', now()->startOfMonth());
            }], 'total_commission_ngn')
            ->orderBy('id');

        if ($search = trim((string) $request->get('q'))) {
            $query->where(function ($w) use ($search) {
                $w->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%')
                    ->orWhere('code', 'like', '%' . $search . '%');
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        return response()->stream(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'name', 'email', 'phone', 'country', 'status', 'code', 'schools_count', 'mtd_commission_ngn', 'total_commission_ngn', 'bank_name', 'account_name', 'account_number', 'created_at']);

            $query->chunk(200, function ($rows) use ($out) {
                foreach ($rows as $a) {
                    fputcsv($out, [
                        $a->id,
                        $a->name,
                        $a->email,
                        $a->phone,
                        $a->country,
                        $a->status,
                        $a->code,
                        $a->schools_count,
                        $a->mtd_commission_ngn ?? 0,
                        $a->total_commission_ngn ?? 0,
                        $a->bank_name,
                        $a->account_name,
                        $a->account_number,
                        $a->created_at,
                    ]);
                }
            });

            fclose($out);
        }, 200, $headers);
    }

    private function generateUniqueReferralCode(): string
    {
        $len = max(6, min(16, (int) config('affiliate.referral_code_length', 8)));
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $code = '';
            for ($i = 0; $i < $len; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (Affiliate::whereRaw('UPPER(code) = ?', [strtoupper($code)])->exists());

        return $code;
    }
}
