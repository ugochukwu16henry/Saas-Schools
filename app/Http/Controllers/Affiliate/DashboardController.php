<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use App\Models\AffiliateCommissionLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:affiliate');
    }

    public function index()
    {
        $affiliate = Auth::guard('affiliate')->user();

        $schools = $affiliate->schools()
            ->withCount(['users as students_count' => function ($q) {
                $q->where('user_type', 'student');
            }])
            ->get()
            ->map(function ($school) {
                $school->billable_count = $school->billableStudentCount();

                return $school;
            });

        $liveMonthlyProjection = $schools->sum(function ($s) {
            return $s->billable_count * (int) config('affiliate.monthly_per_billable_student', 20);
        });

        $totalEarned = AffiliateCommissionLedger::query()
            ->where('affiliate_id', $affiliate->id)
            ->sum('total_commission_ngn');

        $mtdEarned = AffiliateCommissionLedger::query()
            ->where('affiliate_id', $affiliate->id)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('total_commission_ngn');

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
            'liveMonthlyProjection',
            'recentLedger',
            'referralUrl'
        ));
    }

    public function editProfile()
    {
        return view('affiliate.profile', ['affiliate' => Auth::guard('affiliate')->user()]);
    }

    public function updateProfile(Request $request)
    {
        $affiliate = Auth::guard('affiliate')->user();

        $validated = $request->validate([
            'bank_name' => 'nullable|string|max:120',
            'account_number' => 'nullable|string|max:32',
            'account_name' => 'nullable|string|max:120',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        if ($request->hasFile('photo')) {
            if ($affiliate->photo_path) {
                Storage::disk('public')->delete($affiliate->photo_path);
            }
            $validated['photo_path'] = $request->file('photo')->store('affiliate-profiles/'.$affiliate->id, 'public');
        }

        if (! empty($validated['password'])) {
            $affiliate->password = bcrypt($validated['password']);
        }

        $affiliate->fill([
            'bank_name' => $validated['bank_name'] ?? $affiliate->bank_name,
            'account_number' => $validated['account_number'] ?? $affiliate->account_number,
            'account_name' => $validated['account_name'] ?? $affiliate->account_name,
            'photo_path' => $validated['photo_path'] ?? $affiliate->photo_path,
        ]);
        $affiliate->save();

        return redirect()->route('affiliate.profile.edit')->with('status', 'Profile updated.');
    }
}
