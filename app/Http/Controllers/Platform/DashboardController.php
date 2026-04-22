<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    private array $tenantTables = [
        'users',
        'my_classes',
        'sections',
        'subjects',
        'exams',
        'marks',
        'grades',
        'skills',
        'exam_records',
        'student_records',
        'staff_records',
        'payments',
        'payment_records',
        'receipts',
        'time_tables',
        'pins',
        'books',
        'book_requests',
        'settings',
        'dorms',
        'promotions',
    ];

    public function index(Request $request)
    {
        $query = School::query()
            ->with('subscription')
            ->withCount([
                'users as total_users_count',
                'users as students_count' => function ($q) {
                    $q->where('user_type', 'student');
                },
                'users as teachers_count' => function ($q) {
                    $q->where('user_type', 'teacher');
                },
            ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $term = trim($request->q);
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%");
            });
        }

        $schools = $query->latest()->paginate(20)->appends($request->query());

        $stats = [
            'total_schools' => School::count(),
            'active_schools' => School::where('status', 'active')->count(),
            'trial_schools' => School::where('status', 'trial')->count(),
            'suspended_schools' => School::where('status', 'suspended')->count(),
            'students' => DB::table('users')->where('user_type', 'student')->count(),
            'teachers' => DB::table('users')->where('user_type', 'teacher')->count(),
        ];

        return view('platform.dashboard.index', compact('schools', 'stats'));
    }

    public function show(School $school)
    {
        $school->load('subscription')
            ->loadCount([
                'users as total_users_count',
                'users as students_count' => function ($q) {
                    $q->where('user_type', 'student');
                },
                'users as teachers_count' => function ($q) {
                    $q->where('user_type', 'teacher');
                },
                'users as admins_count' => function ($q) {
                    $q->whereIn('user_type', ['admin', 'super_admin']);
                },
            ]);

        return view('platform.dashboard.show', compact('school'));
    }

    public function suspend(School $school)
    {
        $school->update(['status' => 'suspended']);

        return back()->with('status', "{$school->name} has been suspended.");
    }

    public function activate(School $school)
    {
        $school->update(['status' => 'active']);

        return back()->with('status', "{$school->name} has been activated.");
    }

    public function destroy(School $school)
    {
        DB::transaction(function () use ($school) {
            SchoolSubscription::where('school_id', $school->id)->delete();

            foreach ($this->tenantTables as $table) {
                DB::table($table)->where('school_id', $school->id)->delete();
            }

            $school->delete();
        });

        return redirect()->route('platform.dashboard')
            ->with('status', 'School and all tenant data deleted successfully.');
    }
}
