<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckSubscription
{
    public function handle(Request $request, Closure $next)
    {
        if (!app()->bound('currentSchool')) {
            return $next($request);
        }

        $school = app('currentSchool');
        $studentCount = $school->users()->where('user_type', 'student')->count();

        // Always free under the limit
        if ($studentCount <= $school->free_student_limit) {
            return $next($request);
        }

        // Above free limit — check subscription
        $sub = $school->subscription;
        if (!$sub || !$sub->isActive()) {
            return redirect()->route('billing.prompt');
        }

        return $next($request);
    }
}
