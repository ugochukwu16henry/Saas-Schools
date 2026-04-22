<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\Setting;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SchoolRegistrationController extends Controller
{
    public function create()
    {
        return view('auth.register_school');
    }

    public function store(Request $request)
    {
        $request->validate([
            'school_name' => 'required|string|max:255',
            'your_name'   => 'required|string|max:255',
            'email'       => 'required|email|max:255|unique:users,email',
            'password'    => 'required|min:8|confirmed',
        ]);

        DB::transaction(function () use ($request) {
            // Create the school record
            $school = School::create([
                'name'               => $request->school_name,
                'slug'               => $this->uniqueSlug($request->school_name),
                'email'              => $request->email,
                'status'             => 'trial',
                'free_student_limit' => 50,
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
            User::create([
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

        return redirect()->route('login')
            ->with('status', 'School registered successfully! Login to get started. Your first 50 students are free.');
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
        $base = Str::slug($name, '');
        $username = strtolower(substr($base, 0, 20));
        $i = 1;
        while (User::where('username', $username)->exists()) {
            $username = strtolower(substr($base, 0, 18)) . $i++;
        }
        return $username;
    }
}
