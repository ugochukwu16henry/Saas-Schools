<?php

namespace Tests\Feature;

use App\Models\MyClass;
use App\Models\School;
use App\Models\Section;
use App\Models\StudentRecord;
use App\Models\StudentTransfer;
use App\Services\StudentTransferService;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class StudentTransferPhase4Test extends TestCase
{
    use DatabaseTransactions;

    public function testAuditExportReturnsCsvForInboxScope()
    {
        $fromSchool = $this->createSchool('From School', 'from-school', 'from@example.test');
        $toSchool = $this->createSchool('To School', 'to-school', 'to@example.test');

        $this->bindSchool($fromSchool);
        $fromClass = MyClass::query()->create(['name' => 'Grade 6']);
        $fromSection = Section::query()->create([
            'name' => 'A',
            'my_class_id' => $fromClass->id,
            'active' => 1,
        ]);

        $student = $this->createUser('Student Demo', 'student@example.test', 'student', $fromSchool->id);
        $parent = $this->createUser('Parent Demo', 'parent@example.test', 'parent', $fromSchool->id);

        StudentRecord::query()->create([
            'user_id' => $student->id,
            'my_class_id' => $fromClass->id,
            'section_id' => $fromSection->id,
            'my_parent_id' => $parent->id,
            'session' => '2025/2026',
            'adm_no' => 'ADM-001',
        ]);

        $requester = $this->createUser('Sender Admin', 'sender-admin@example.test', 'super_admin', $fromSchool->id);
        $receiver = $this->createUser('Receiver Admin', 'receiver-admin@example.test', 'super_admin', $toSchool->id);

        StudentTransfer::query()->create([
            'student_id' => $student->id,
            'from_school_id' => $fromSchool->id,
            'to_school_id' => $toSchool->id,
            'requested_by' => $requester->id,
            'accepted_by' => $receiver->id,
            'status' => StudentTransfer::STATUS_ACCEPTED,
            'from_class_id' => $fromClass->id,
            'from_section_id' => $fromSection->id,
            'from_session' => '2025/2026',
            'transfer_note' => 'Transfer for relocation.',
            'transferred_at' => now(),
            'transfer_snapshot' => [
                'student' => ['name' => $student->name, 'code' => $student->code],
                'parent' => ['name' => $parent->name],
                'academic' => ['class_name' => 'Grade 6', 'section_name' => 'A'],
            ],
            'status_history' => [
                ['event' => 'requested', 'status' => 'pending', 'at' => now()->subDay()->toDateTimeString()],
                ['event' => 'accepted', 'status' => 'accepted', 'at' => now()->toDateTimeString()],
            ],
        ]);

        $this->withoutMiddleware();
        $response = $this->actingAs($receiver)->get(route('transfers.audit.export', ['scope' => 'inbox']));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('transfer_id,scope,status,student_name', $content);
        $this->assertStringContainsString('Student Demo', $content);
        $this->assertStringContainsString('accepted', $content);
    }

    public function testAcceptTransferFailsWhenDestinationClassMappingIsMissing()
    {
        $fromSchool = $this->createSchool('Alpha School', 'alpha-school', 'alpha@example.test');
        $toSchool = $this->createSchool('Beta School', 'beta-school', 'beta@example.test');

        $this->bindSchool($fromSchool);
        $fromClass = MyClass::query()->create(['name' => 'JSS 1']);
        $fromSection = Section::query()->create([
            'name' => 'Blue',
            'my_class_id' => $fromClass->id,
            'active' => 1,
        ]);

        $student = $this->createUser('Transfer Student', 'transfer-student@example.test', 'student', $fromSchool->id);
        StudentRecord::query()->create([
            'user_id' => $student->id,
            'my_class_id' => $fromClass->id,
            'section_id' => $fromSection->id,
            'session' => '2025/2026',
            'adm_no' => 'ADM-200',
        ]);

        $requestedBy = $this->createUser('Requested By', 'requested-by@example.test', 'super_admin', $fromSchool->id);
        $acceptedBy = $this->createUser('Accepted By', 'accepted-by@example.test', 'super_admin', $toSchool->id);

        $transfer = StudentTransfer::query()->create([
            'student_id' => $student->id,
            'from_school_id' => $fromSchool->id,
            'to_school_id' => $toSchool->id,
            'requested_by' => $requestedBy->id,
            'status' => StudentTransfer::STATUS_PENDING,
            'from_class_id' => $fromClass->id,
            'from_section_id' => $fromSection->id,
            'from_session' => '2025/2026',
            'transfer_snapshot' => [
                'academic' => [
                    'class_name' => 'JSS 1',
                    'section_name' => 'Blue',
                ],
            ],
            'status_history' => [
                ['event' => 'requested', 'status' => 'pending', 'at' => now()->toDateTimeString()],
            ],
        ]);

        $service = app(StudentTransferService::class);

        try {
            $service->acceptTransfer($transfer, $acceptedBy);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('no matching class', strtolower($e->getMessage()));
        }

        $this->assertSame(StudentTransfer::STATUS_PENDING, (string) $transfer->fresh()->status);
        $this->assertSame($fromSchool->id, (int) $student->fresh()->school_id);
    }

    private function createSchool(string $name, string $slug, string $email): School
    {
        return School::withoutEvents(function () use ($name, $slug, $email) {
            return School::query()->create([
                'name' => $name,
                'slug' => $slug,
                'email' => $email,
                'status' => 'active',
                'free_student_limit' => 50,
            ]);
        });
    }

    private function createUser(string $name, string $email, string $type, int $schoolId): User
    {
        return User::query()->create([
            'name' => $name,
            'email' => $email,
            'code' => strtoupper(substr($type, 0, 3)) . '-' . substr(md5($email), 0, 7),
            'username' => null,
            'user_type' => $type,
            'password' => Hash::make('secret12345'),
            'school_id' => $schoolId,
        ]);
    }

    private function bindSchool(School $school): void
    {
        app()->instance('currentSchool', $school);
    }
}
