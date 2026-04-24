<?php

namespace App\Console\Commands;

use App\Models\MyClass;
use App\Services\StudentAdmissionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestStudentCreation extends Command
{
    protected $signature = 'students:test-create {--count=5}';
    protected $description = 'Test student creation and bulk import to diagnose issues';

    public function handle(StudentAdmissionService $admissionService)
    {
        $school = auth('web')->check() ? auth('web')->user()->school : DB::table('schools')->first();
        if (!$school) {
            $this->error('No school context found. Run this command while logged in as a school user.');
            return 1;
        }

        $count = (int) $this->option('count');
        $this->info("=== TESTING STUDENT CREATION ($count students) ===\n");

        // Get a valid class
        $class = MyClass::query()->first();
        if (!$class) {
            $this->error('No classes found in the system.');
            return 1;
        }

        $this->info("Using class: {$class->name} (ID: {$class->id})");
        $this->newLine();

        $section = $class->sections()->first();
        if (!$section) {
            $this->error("Class {$class->name} has no sections. Cannot create students.");
            return 1;
        }

        $this->info("Using section: {$section->name} (ID: {$section->id})");
        $this->newLine();

        $successCount = 0;
        $failCount = 0;

        for ($i = 1; $i <= $count; $i++) {
            try {
                $this->info("Creating student $i/$count...");

                $userRecord = [
                    'name' => "Test Student $i",
                    'email' => 'test-student-' . time() . '-' . $i . '@example.com',
                    'phone' => '080' . str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT),
                    'gender' => $i % 2 === 0 ? 'Female' : 'Male',
                    'address' => 'Test Address ' . $i,
                    'nal_id' => 1,
                    'state_id' => 1,
                    'lga_id' => 1,
                    'school_id' => $school->id,
                ];

                $studentRecord = [
                    'my_class_id' => $class->id,
                    'section_id' => $section->id,
                    'year_admitted' => date('Y'),
                    'house' => null,
                    'age' => null,
                ];

                $user = $admissionService->admitStudent($userRecord, $studentRecord, null, null);

                $this->line("  ✓ Created: {$user->name} (Code: {$user->code}, ID: {$user->id})");
                $successCount++;
            } catch (\Exception $e) {
                $this->error("  ✗ Error: {$e->getMessage()}");
                $failCount++;
            }

            $this->newLine();
        }

        $this->newLine();
        $this->info("=== TEST RESULTS ===");
        $this->line("Successfully created: $successCount");
        $this->line("Failed: $failCount");
        $this->newLine();

        // Verify database state
        $totalStudents = DB::table('users')->where('user_type', 'student')->count();
        $this->info("Total students in database: $totalStudents");

        $duplicateCodes = DB::table('users')
            ->select('code', DB::raw('COUNT(*) as count'))
            ->groupBy('code')
            ->having('count', '>', 1)
            ->count();

        if ($duplicateCodes > 0) {
            $this->error("⚠ WARNING: Found duplicate codes in database!");
        } else {
            $this->info("✓ No duplicate codes detected");
        }

        return $failCount > 0 ? 1 : 0;
    }
}
