<?php

namespace App\Http\Controllers;

use App\Models\MyClass;
use App\Models\School;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;

class OnboardingController extends Controller
{
    public function index()
    {
        $school = app('currentSchool');
        $steps = $this->stepsForSchool($school);
        $completedCount = collect($steps)->where('done', true)->count();
        $totalCount = count($steps);
        $allDone = $completedCount === $totalCount;

        return view('onboarding.index', compact('school', 'steps', 'completedCount', 'totalCount', 'allDone'));
    }

    public function complete(): RedirectResponse
    {
        $school = app('currentSchool');
        $steps = $this->stepsForSchool($school);
        $allDone = collect($steps)->every(static fn($step) => (bool) ($step['done'] ?? false));

        if (! $allDone) {
            return redirect()->route('onboarding.index')->withErrors([
                'onboarding' => 'Complete all setup steps before marking onboarding complete.',
            ]);
        }

        $school->update(['onboarding_completed_at' => now()]);

        return redirect()->route('onboarding.index')->with('status', 'Onboarding marked as complete. Great work!');
    }

    private function stepsForSchool(School $school): array
    {
        $profileDone = !empty($school->name)
            && !empty($school->email)
            && !empty($school->phone)
            && !empty($school->address)
            && !empty($school->logo);

        $classesDone = MyClass::query()->count() > 0;
        $subjectsDone = Subject::query()->count() > 0;
        $teachersDone = $school->users()->where('user_type', 'teacher')->count() > 0;
        $studentsDone = $school->users()->where('user_type', 'student')->count() > 0;

        return [
            [
                'label' => 'Complete school profile (logo, phone, address)',
                'done' => $profileDone,
                'action_label' => 'Open Settings',
                'action_route' => route('settings'),
            ],
            [
                'label' => 'Create first class',
                'done' => $classesDone,
                'action_label' => 'Create Class',
                'action_route' => route('classes.create'),
            ],
            [
                'label' => 'Create first subject',
                'done' => $subjectsDone,
                'action_label' => 'Create Subject',
                'action_route' => route('subjects.create'),
            ],
            [
                'label' => 'Invite/add first teacher',
                'done' => $teachersDone,
                'action_label' => 'Add User',
                'action_route' => route('users.create'),
            ],
            [
                'label' => 'Admit first student',
                'done' => $studentsDone,
                'action_label' => 'Admit Student',
                'action_route' => route('students.create'),
            ],
        ];
    }
}
