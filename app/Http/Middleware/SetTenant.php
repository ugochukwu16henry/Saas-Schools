<?php

namespace App\Http\Middleware;

use App\Models\School;
use Closure;
use Illuminate\Http\Request;

class SetTenant
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return $next($request);
        }

        $schoolId = auth()->user()->school_id;

        if (!$schoolId) {
            // Platform-level admin with no school_id — allow through
            return $next($request);
        }

        $school = School::find($schoolId);

        if (!$school) {
            auth()->logout();
            return redirect()->route('login')
                ->withErrors(['email' => 'Your school account could not be found.']);
        }

        if ($school->status === 'suspended') {
            auth()->logout();
            return redirect()->route('login')
                ->withErrors(['email' => 'Your school account has been suspended. Please contact support.']);
        }

        app()->instance('currentSchool', $school);

        // Share school with all views
        view()->share('currentSchool', $school);

        return $next($request);
    }
}
