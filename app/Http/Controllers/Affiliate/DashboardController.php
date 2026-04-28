<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AffiliateCommissionLedger;
use App\Models\AffiliatePayout;
use App\Models\BillingPlan;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:affiliate');
    }

    public function index()
    {
        /** @var Affiliate $affiliate */
        $affiliate = Auth::guard('affiliate')->user();

        if (empty($affiliate->code)) {
            $affiliate->code = $this->generateReferralCode();
            $affiliate->save();
        }

        $schools = $affiliate->schools()
            ->with('subscription:id,school_id,status,next_payment_date')
            ->withCount(['users as students_count' => function ($q) {
                $q->where('user_type', 'student');
            }])
            ->get()
            ->map(function ($school) {
                $school->billable_count = $school->billableStudentCount();

                return $school;
            });

        $liveMonthlyProjection = $schools->sum(function ($s) {
            return $s->billable_count * $s->effectiveAffiliateMonthlyCommissionRate();
        });

        $defaultPlan = BillingPlan::defaultActive();
        $defaultAffiliateOneTimeRate = $defaultPlan
            ? (int) $defaultPlan->affiliate_one_time_commission_per_student
            : BillingPlan::DEFAULT_AFFILIATE_ONE_TIME_COMMISSION_NGN;
        $defaultAffiliateMonthlyRate = $defaultPlan
            ? (int) $defaultPlan->affiliate_monthly_commission_per_student
            : BillingPlan::DEFAULT_AFFILIATE_MONTHLY_COMMISSION_NGN;

        $totalEarned = AffiliateCommissionLedger::query()
            ->where('affiliate_id', $affiliate->id)
            ->sum('total_commission_ngn');

        $mtdEarned = AffiliateCommissionLedger::query()
            ->where('affiliate_id', $affiliate->id)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('total_commission_ngn');

        $totalPaid = AffiliatePayout::query()
            ->where('affiliate_id', $affiliate->id)
            ->where('status', 'paid')
            ->sum('amount_ngn');

        $pendingPayouts = AffiliatePayout::query()
            ->where('affiliate_id', $affiliate->id)
            ->where('status', 'pending')
            ->sum('amount_ngn');

        $availableForPayout = max(0, (int) $totalEarned - (int) $totalPaid - (int) $pendingPayouts);

        $recentPayouts = AffiliatePayout::query()
            ->where('affiliate_id', $affiliate->id)
            ->latest('id')
            ->limit(25)
            ->get();

        $schoolsByStatus = [
            'active' => $schools->where('status', 'active')->count(),
            'trial' => $schools->where('status', 'trial')->count(),
            'suspended' => $schools->where('status', 'suspended')->count(),
        ];

        $recentLedger = AffiliateCommissionLedger::query()
            ->where('affiliate_id', $affiliate->id)
            ->with('school:id,name')
            ->orderByDesc('created_at')
            ->limit(25)
            ->get();

        $referralUrl = route('school.register', ['ref' => $affiliate->code]);

        return view('affiliate.dashboard', compact(
            'affiliate',
            'schools',
            'totalEarned',
            'mtdEarned',
            'totalPaid',
            'pendingPayouts',
            'availableForPayout',
            'recentPayouts',
            'schoolsByStatus',
            'liveMonthlyProjection',
            'recentLedger',
            'referralUrl',
            'defaultAffiliateOneTimeRate',
            'defaultAffiliateMonthlyRate'
        ));
    }

    private function generateReferralCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (\App\Models\Affiliate::where('code', $code)->exists());

        return $code;
    }

    public function editProfile()
    {
        return view('affiliate.profile', ['affiliate' => Auth::guard('affiliate')->user()]);
    }

    public function updateProfile(Request $request)
    {
        /** @var Affiliate $affiliate */
        $affiliate = Auth::guard('affiliate')->user();

        // Validate text fields via input() to avoid touching the files bag,
        // which crashes when a stray string arrives where a SymfonyUploadedFile is expected.
        $textValidator = Validator::make($request->input(), [
            'bank_name'      => 'nullable|string|max:120',
            'account_number' => 'nullable|string|max:32',
            'account_name'   => 'nullable|string|max:120',
            'password'       => 'nullable|string|min:8|confirmed',
        ]);
        $textValidator->validate();
        $validated = $textValidator->validated();

        // Resolve photo safely from the files bag only.
        $photoFile = $request->files->get('photo');
        if ($photoFile instanceof UploadedFile && $photoFile->isValid()) {
            $fileValidator = Validator::make(
                ['photo' => $photoFile],
                ['photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048']
            );
            $fileValidator->validate();

            if ($affiliate->photo_path) {
                Storage::disk('public')->delete($affiliate->photo_path);
            }
            $validated['photo_path'] = $photoFile->store('affiliate-profiles/' . $affiliate->id, 'public');
        }

        if (! empty($validated['password'])) {
            $affiliate->password = bcrypt($validated['password']);
        }

        $affiliate->fill([
            'bank_name'      => $validated['bank_name'] ?? $affiliate->bank_name,
            'account_number' => $validated['account_number'] ?? $affiliate->account_number,
            'account_name'   => $validated['account_name'] ?? $affiliate->account_name,
            'photo_path'     => $validated['photo_path'] ?? $affiliate->photo_path,
        ]);
        $affiliate->save();

        return redirect()->route('affiliate.profile.edit')->with('status', 'Profile updated.');
    }
}
