<?php

namespace Tests\Feature;

use App\Models\MyClass;
use App\Models\School;
use App\Models\Section;
use App\Models\StudentRecord;
use App\Models\StudentTransfer;
use App\Services\StudentTransferService;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class StudentTransferPhase4Test extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (!Schema::hasTable('student_transfers')) {
            Schema::create('student_transfers', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->unsignedInteger('student_id');
                $table->unsignedBigInteger('from_school_id');
                $table->unsignedBigInteger('to_school_id');
                $table->unsignedInteger('requested_by');
                $table->unsignedInteger('accepted_by')->nullable();
                $table->enum('status', ['pending', 'accepted', 'rejected', 'cancelled'])->default('pending');
                $table->unsignedInteger('from_class_id')->nullable();
                $table->unsignedInteger('from_section_id')->nullable();
                $table->string('from_session')->nullable();
                $table->text('transfer_note')->nullable();
                $table->text('rejected_reason')->nullable();
                $table->dateTime('transferred_at')->nullable();
                $table->json('transfer_snapshot')->nullable();
                $table->json('status_history')->nullable();
                $table->timestamps();
            });
        }
    }

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
            'adm_no' => 'ADM-' . strtoupper(substr(uniqid('', true), -6)),
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

        $this->bindSchool($toSchool);
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
            'adm_no' => 'ADM-' . strtoupper(substr(uniqid('', true), -6)),
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
                'student' => [
                    'name' => $student->name,
                ],
                'parent' => [
                    'name' => 'Snapshot Parent',
                ],
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

    public function testAcceptTransferAllowsDeferClassAssignmentWhenDestinationClassMissing()
    {
        $fromSchool = $this->createSchool('From School', 'from-school3', 'from3@example.test');
        $toSchool = $this->createSchool('To School', 'to-school3', 'to3@example.test');

        // Destination class/section NOT created yet.
        $this->bindSchool($fromSchool);

        $fromClass = MyClass::query()->create(['name' => 'Upper Resection']);
        $fromSection = Section::query()->create([
            'name' => 'Blue',
            'my_class_id' => $fromClass->id,
            'active' => 1,
        ]);

        $student = $this->createUser('Defer Class Student', 'defer-class-student@example.test', 'student', $fromSchool->id);
        StudentRecord::query()->create([
            'user_id' => $student->id,
            'my_class_id' => $fromClass->id,
            'section_id' => $fromSection->id,
            'session' => '2025/2026',
            'adm_no' => 'ADM-' . strtoupper(substr(uniqid('', true), -6)),
        ]);

        $parent = $this->createUser('Defer Class Parent', 'defer-class-parent@example.test', 'parent', $fromSchool->id);

        $requestedBy = $this->createUser('Requested By', 'requested-by3@example.test', 'super_admin', $fromSchool->id);
        $acceptedBy = $this->createUser('Accepted By', 'accepted-by3@example.test', 'super_admin', $toSchool->id);

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
                'student' => [
                    'name' => $student->name,
                    'code' => $student->code,
                ],
                'parent' => [
                    'name' => $parent->name,
                ],
                'academic' => [
                    'class_name' => 'Upper Resection',
                    'section_name' => 'Blue',
                ],
            ],
            'status_history' => [
                ['event' => 'requested', 'status' => 'pending', 'at' => now()->toDateTimeString()],
            ],
        ]);

        $service = app(StudentTransferService::class);

        // Defer class assignment should prevent mapping exceptions.
        $service->acceptTransfer($transfer, $acceptedBy, true);

        $this->assertSame(StudentTransfer::STATUS_ACCEPTED, (string) $transfer->fresh()->status);
        $this->assertSame($toSchool->id, (int) $student->fresh()->school_id);
    }

    public function testTransferInboxRendersStudentPhotoFromSendingSchool()
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

        $student = $this->createUser('Student Demo', 'student-photo@example.test', 'student', $fromSchool->id);
        $student->update(['photo' => 'uploads/student/photo1.jpg']);

        $parent = $this->createUser('Parent Demo', 'parent-photo@example.test', 'parent', $fromSchool->id);

        StudentRecord::query()->create([
            'user_id' => $student->id,
            'my_class_id' => $fromClass->id,
            'section_id' => $fromSection->id,
            'my_parent_id' => $parent->id,
            'session' => '2025/2026',
            'adm_no' => 'ADM-' . strtoupper(substr(uniqid('', true), -6)),
        ]);

        $requester = $this->createUser('Sender Admin', 'sender-admin-photo@example.test', 'super_admin', $fromSchool->id);
        $receiver = $this->createUser('Receiver Admin', 'receiver-admin-photo@example.test', 'super_admin', $toSchool->id);

        StudentTransfer::query()->create([
            'student_id' => $student->id,
            'from_school_id' => $fromSchool->id,
            'to_school_id' => $toSchool->id,
            'requested_by' => $requester->id,
            'accepted_by' => null,
            'status' => StudentTransfer::STATUS_PENDING,
            'from_class_id' => $fromClass->id,
            'from_section_id' => $fromSection->id,
            'from_session' => '2025/2026',
            'transfer_note' => 'Transfer for relocation.',
        ]);

        // View the inbox from the receiving school (student is still in sending school).
        $this->bindSchool($toSchool);

        $response = $this->actingAs($receiver)->get(route('transfers.inbox'));
        $response->assertOk();

        // Photo accessor converts `uploads/...` into `/storage/uploads/...`.
        $this->assertStringContainsString('storage/uploads/student/photo1.jpg', (string) $response->getContent());
    }

    public function testTransferOutboxRendersStudentPhotoAfterStudentMoved()
    {
        $fromSchool = $this->createSchool('From School', 'from-school2', 'from2@example.test');
        $toSchool = $this->createSchool('To School', 'to-school2', 'to2@example.test');

        // Create student at the receiving school (i.e., after acceptance).
        $this->bindSchool($toSchool);
        $toClass = MyClass::query()->create(['name' => 'Grade 7']);
        $toSection = Section::query()->create([
            'name' => 'B',
            'my_class_id' => $toClass->id,
            'active' => 1,
        ]);

        $student = $this->createUser('Moved Student', 'moved-student-photo@example.test', 'student', $toSchool->id);
        $student->update(['photo' => 'uploads/student/photo2.jpg']);

        $parent = $this->createUser('Moved Parent', 'moved-parent-photo@example.test', 'parent', $toSchool->id);

        StudentRecord::query()->create([
            'user_id' => $student->id,
            'my_class_id' => $toClass->id,
            'section_id' => $toSection->id,
            'my_parent_id' => $parent->id,
            'session' => '2025/2026',
            'adm_no' => 'ADM-' . strtoupper(substr(uniqid('', true), -6)),
        ]);

        $requester = $this->createUser('Sender Admin', 'sender-admin-photo2@example.test', 'super_admin', $fromSchool->id);
        $acceptedBy = $this->createUser('Receiver Admin', 'receiver-admin-photo2@example.test', 'super_admin', $toSchool->id);

        StudentTransfer::query()->create([
            'student_id' => $student->id,
            'from_school_id' => $fromSchool->id,
            'to_school_id' => $toSchool->id,
            'requested_by' => $requester->id,
            'accepted_by' => $acceptedBy->id,
            'status' => StudentTransfer::STATUS_ACCEPTED,
            'from_class_id' => $toClass->id,
            'from_section_id' => $toSection->id,
            'from_session' => '2025/2026',
            'transferred_at' => now(),
            'transfer_note' => 'Accepted transfer.',
        ]);

        // View the outbox from the sending school (student has already moved).
        $this->bindSchool($fromSchool);

        $outboxViewer = $this->createUser('Outbox Viewer', 'outbox-viewer@example.test', 'super_admin', $fromSchool->id);
        $response = $this->actingAs($outboxViewer)->get(route('transfers.outbox'));
        $response->assertOk();

        $this->assertStringContainsString('storage/uploads/student/photo2.jpg', (string) $response->getContent());
    }

    private function createSchool(string $name, string $slug, string $email): School
    {
        $suffix = strtolower(substr(uniqid('', true), -8));

        return School::withoutEvents(function () use ($name, $slug, $email, $suffix) {
            return School::query()->create([
                'name' => $name,
                'slug' => $slug . '-' . $suffix,
                'email' => preg_replace('/@/', '+' . $suffix . '@', $email, 1),
                'status' => 'active',
                'free_student_limit' => 50,
            ]);
        });
    }

    private function createUser(string $name, string $email, string $type, int $schoolId): User
    {
        $suffix = strtolower(substr(uniqid('', true), -8));
        $uniqueEmail = preg_replace('/@/', '+' . $suffix . '@', $email, 1);

        return User::query()->create([
            'name' => $name,
            'email' => $uniqueEmail,
            'code' => strtoupper(substr($type, 0, 3)) . '-' . substr(md5($uniqueEmail), 0, 7),
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
