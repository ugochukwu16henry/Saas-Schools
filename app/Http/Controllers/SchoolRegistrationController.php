<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\BillingPlan;
use App\Models\Setting;
use App\Notifications\SchoolWelcomeNotification;
use App\Services\AffiliateReferralService;
use App\Services\PlatformNotificationService;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SchoolRegistrationController extends Controller
{
    public function create(Request $request)
    {
        $ref = $request->query('ref');
        if (is_string($ref) && trim($ref) !== '') {
            $request->session()->put('school_registration_ref', strtoupper(trim($ref)));
        }

        $registrationRef = $request->session()->get('school_registration_ref');
        $defaultPlan = BillingPlan::defaultActive();

        return view('auth.register_school', [
            'registration_ref' => $registrationRef,
            'freeLimit' => $defaultPlan
                ? (int) $defaultPlan->default_free_student_limit
                : BillingPlan::DEFAULT_FREE_STUDENT_LIMIT,
            'monthlyRate' => $defaultPlan
                ? (int) $defaultPlan->monthly_rate_per_student
                : BillingPlan::DEFAULT_MONTHLY_RATE_PER_STUDENT,
            'oneTimeRate' => $defaultPlan
                ? (int) $defaultPlan->one_time_add_rate
                : BillingPlan::DEFAULT_ONE_TIME_ADD_RATE,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'school_name' => 'required|string|max:255',
            'your_name'   => 'required|string|max:255',
            'email'       => 'required|email|max:255|unique:users,email',
            'password'    => 'required|min:8|confirmed',
        ]);

        $newUser = null;
        $newSchool = null;

        DB::transaction(function () use ($request, &$newUser, &$newSchool) {
            $referral = trim((string) ($request->input('ref') ?: $request->session()->get('school_registration_ref')));
            $affiliateId = app(AffiliateReferralService::class)->resolveAffiliateId($referral !== '' ? $referral : null);
            $defaultPlan = BillingPlan::defaultActive();

            // Create the school record
            $newSchool = $school = School::create([
                'name'               => $request->school_name,
                'slug'               => $this->uniqueSlug($request->school_name),
                'email'              => $request->email,
                'status'             => 'trial',
                'free_student_limit' => (int) ($defaultPlan->default_free_student_limit ?? BillingPlan::DEFAULT_FREE_STUDENT_LIMIT),
                'billing_plan_id'    => $defaultPlan ? $defaultPlan->id : null,
                'affiliate_id'       => $affiliateId,
                'affiliate_attributed_at' => $affiliateId ? now() : null,
            ]);

            // Bind school so BelongsToSchool trait assigns school_id automatically
            app()->instance('currentSchool', $school);

            // Seed default settings for this school
            $year = date('Y');
            $nextYear = $year + 1;
            $defaults = [
                ['type' => 'current_session', 'description' => "{$year}-{$nextYear}"],
                ['type' => 'system_title',    'description' => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $request->school_name), 0, 6))],
                ['type' => 'system_name',     'description' => $request->school_name],
                ['type' => 'term_ends',       'description' => ''],
                ['type' => 'term_begins',     'description' => ''],
                ['type' => 'phone',           'description' => ''],
                ['type' => 'address',         'description' => ''],
                ['type' => 'system_email',    'description' => $request->email],
                ['type' => 'alt_email',       'description' => ''],
                ['type' => 'email_host',      'description' => ''],
                ['type' => 'email_pass',      'description' => ''],
                ['type' => 'lock_exam',       'description' => '0'],
                ['type' => 'logo',            'description' => ''],
                ['type' => 'next_term_fees_j',  'description' => '0'],
                ['type' => 'next_term_fees_pn', 'description' => '0'],
                ['type' => 'next_term_fees_p',  'description' => '0'],
                ['type' => 'next_term_fees_n',  'description' => '0'],
                ['type' => 'next_term_fees_s',  'description' => '0'],
                ['type' => 'next_term_fees_c',  'description' => '0'],
            ];

            foreach ($defaults as $row) {
                Setting::create($row); // school_id auto-assigned via trait
            }

            // Create the school owner as super_admin
            $newUser = User::create([
                'name'      => $request->your_name,
                'email'     => $request->email,
                'username'  => $this->uniqueUsername($request->your_name),
                'password'  => Hash::make($request->password),
                'user_type' => 'super_admin',
                'school_id' => $school->id,
                'code'      => strtoupper(Str::random(10)),
                'photo'     => \App\Helpers\Qs::getDefaultUserImage(),
            ]);
        });

        $request->session()->forget('school_registration_ref');

        if ($newUser && $newSchool) {
            $newUser->notify(new SchoolWelcomeNotification($newSchool));
            app(PlatformNotificationService::class)->schoolRegistered($newSchool, $newUser);
        }

        $freeStudentLimit = $newSchool
            ? $newSchool->effectiveFreeStudentLimit()
            : BillingPlan::DEFAULT_FREE_STUDENT_LIMIT;

        return redirect()->route('login')
            ->with('status', 'School registered successfully! Login to get started. Your first ' . number_format($freeStudentLimit) . ' students are free.');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;
        while (School::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    private function uniqueUsername(string $name): string
    {
        // Username must be globally unique, so we bypass tenant scopes.
        $base = Str::slug($name, '');
        $base = $base !== '' ? strtolower($base) : 'user';
        $username = substr($base, 0, 20);
        $i = 1;
        while (User::withoutGlobalScopes()->where('username', $username)->exists()) {
            $suffix = (string) $i++;
            $username = substr($base, 0, 20 - strlen($suffix)) . $suffix;
        }
        return $username;
    }
}
