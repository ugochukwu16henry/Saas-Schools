<?php

namespace App\Console\Commands;

use App\Models\MyClass;
use App\Services\StudentAdmissionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DebugStudentCreation extends Command
{
    protected $signature = 'students:debug-create';
    protected $description = 'Debug student creation to find the exact error';

    public function handle(StudentAdmissionService $admissionService)
    {
        $this->info("=== DEBUGGING STUDENT CREATION ===\n");

        // Get a school from database (or use first school if no auth context)
        $school = DB::table('schools')->first();
        if (!$school) {
            $this->error('No schools found in the system.');
            return 1;
        }

        $this->info("Using school: {$school->name} (ID: {$school->id})");

        // Get a valid class
        $class = MyClass::query()->first();
        if (!$class) {
            $this->error('No classes found in the system.');
            return 1;
        }

        $this->info("Using class: {$class->name} (ID: {$class->id})");

        $section = $class->section()->first();
        if (!$section) {
            $this->error("Class {$class->name} has no sections.");
            return 1;
        }

        $this->info("Using section: {$section->name} (ID: {$section->id})\n");

        try {
            $this->info("Attempting to create a student...");

            $userRecord = [
                'name' => 'Debug Student Test ' . time(),
                'email' => 'debug-' . time() . '@example.com',
                'phone' => '08012345678',
                'gender' => 'Male',
                'address' => 'Test Address',
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

            $this->line("User data: " . json_encode($userRecord));
            $this->line("Student data: " . json_encode($studentRecord));
            $this->newLine();

            $user = $admissionService->admitStudent($userRecord, $studentRecord, null, null);

            $this->info("✓ SUCCESS! Created student: {$user->name}");
            $this->info("  Code: {$user->code}");
            $this->info("  Username: {$user->username}");
            $this->info("  ID: {$user->id}");

            return 0;
        } catch (\Exception $e) {
            $this->error("✗ FAILED with error:");
            $this->error($e->getMessage());
            $this->error("\nStack trace:");
            $this->error($e->getTraceAsString());

            return 1;
        }
    }
}
