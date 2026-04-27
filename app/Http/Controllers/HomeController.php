<?php

namespace App\Http\Controllers;

use App\Helpers\Qs;
use App\Repositories\UserRepo;

class HomeController extends Controller
{
    protected $user;
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
        }

        return view('pages.support_team.dashboard', $d);
    }
}
