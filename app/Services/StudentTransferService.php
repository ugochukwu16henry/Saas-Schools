<?php

namespace App\Services;

use App\Models\ExamRecord;
use App\Models\Mark;
use App\Models\Promotion;
use App\Models\School;
use App\Models\SchoolSubscription;
use App\Models\StudentQrToken;
use App\Models\StudentRecord;
use App\Models\StudentTransfer;
use App\Notifications\StudentTransferAcceptedNotification;
use App\Notifications\StudentTransferRejectedNotification;
use App\Notifications\StudentTransferRequestedNotification;
use App\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class StudentTransferService
{
    public function buildTransferDetails(StudentTransfer $transfer, User $viewer): array
    {
        $transfer = StudentTransfer::query()->findOrFail($transfer->id);
        $this->appendTransferHistory($transfer, [
            'event' => 'viewed',
            'status' => (string) $transfer->status,
            'actor_id' => (int) $viewer->id,
            'actor_name' => (string) $viewer->name,
            'actor_school_id' => (int) ($viewer->school_id ?? 0),
        ]);

        $transfer = StudentTransfer::query()
            ->with([
                'student',
                'fromSchool',
                'toSchool',
                'requestedBy',
                'acceptedBy',
            ])
            ->findOrFail($transfer->id);

        $student = User::withoutGlobalScopes()
            ->with('school')
            ->findOrFail((int) $transfer->student_id);

        $studentRecord = StudentRecord::withoutGlobalScopes()
            ->with(['my_class', 'section', 'my_parent'])
            ->where('user_id', (int) $transfer->student_id)
            ->first();

        $parent = $studentRecord && $studentRecord->my_parent_id
            ? User::withoutGlobalScopes()->find($studentRecord->my_parent_id)
            : null;

        $examRecordsQuery = ExamRecord::withoutGlobalScopes()
            ->where('student_id', (int) $transfer->student_id);

        $marksQuery = Mark::withoutGlobalScopes()
            ->where('student_id', (int) $transfer->student_id);

        $promotionsQuery = Promotion::withoutGlobalScopes()
            ->where('student_id', (int) $transfer->student_id);

        $examRecordsCount = (int) (clone $examRecordsQuery)->count();
        $marksCount = (int) (clone $marksQuery)->count();
        $promotionsCount = (int) (clone $promotionsQuery)->count();

        $recentExamRecords = (clone $examRecordsQuery)
            ->with(['exam', 'my_class', 'section'])
            ->latest('id')
            ->limit(10)
            ->get();

        $recentMarks = (clone $marksQuery)
            ->with(['subject', 'exam', 'my_class', 'section', 'grade'])
            ->latest('id')
            ->limit(20)
            ->get();

        return [
            'transfer' => $transfer,
            'student' => $student,
            'studentRecord' => $studentRecord,
            'parent' => $parent,
            'examRecordsCount' => $examRecordsCount,
            'marksCount' => $marksCount,
            'promotionsCount' => $promotionsCount,
            'recentExamRecords' => $recentExamRecords,
            'recentMarks' => $recentMarks,
            'transferSnapshot' => $transfer->transfer_snapshot ?: [],
            'statusHistory' => $transfer->status_history ?: [],
            'transcriptUrl' => route('students.transcript.show', $student->id),
            'transcriptDownloadUrl' => route('students.transcript.download', $student->id),
        ];
    }

    public function initiateTransfer(User $student, School $toSchool, User $requestedBy, ?string $note = null): StudentTransfer
    {
        if ($student->user_type !== 'student') {
            throw new RuntimeException('Only student accounts can be transferred.');
        }

        $fromSchoolId = (int) ($student->school_id ?? 0);
        if ($fromSchoolId <= 0) {
            throw new RuntimeException('Student is not linked to any school.');
        }

        if ($fromSchoolId === (int) $toSchool->id) {
            throw new RuntimeException('Student is already in the selected school.');
        }

        if ((int) ($requestedBy->school_id ?? 0) !== $fromSchoolId) {
            throw new RuntimeException('You can only transfer students from your own school.');
        }

        $existingPending = StudentTransfer::query()
            ->where('student_id', $student->id)
            ->where('status', StudentTransfer::STATUS_PENDING)
            ->exists();

        if ($existingPending) {
            throw new RuntimeException('This student already has a pending transfer request.');
        }

        $record = StudentRecord::withoutGlobalScopes()
            ->where('user_id', $student->id)
            ->first();

        $parent = $record && $record->my_parent_id
            ? User::withoutGlobalScopes()->find($record->my_parent_id)
            : null;

        $snapshot = $this->buildTransferSnapshot($student, $record, $parent, $toSchool, $requestedBy);
        $initialHistory = [[
            'event' => 'requested',
            'status' => StudentTransfer::STATUS_PENDING,
            'actor_id' => (int) $requestedBy->id,
            'actor_name' => (string) $requestedBy->name,
            'actor_school_id' => (int) ($requestedBy->school_id ?? 0),
            'at' => now()->toDateTimeString(),
        ]];

        $transfer = StudentTransfer::create([
            'student_id' => (int) $student->id,
            'from_school_id' => $fromSchoolId,
            'to_school_id' => (int) $toSchool->id,
            'requested_by' => (int) $requestedBy->id,
            'status' => StudentTransfer::STATUS_PENDING,
            'from_class_id' => $record ? (int) $record->my_class_id : null,
            'from_section_id' => $record ? (int) $record->section_id : null,
            'from_session' => $record ? (string) $record->session : null,
            'transfer_note' => $note,
            'transfer_snapshot' => $snapshot,
            'status_history' => $initialHistory,
        ]);

        $transfer = $transfer->fresh(['student', 'fromSchool', 'toSchool', 'requestedBy']);
        $this->notifyUsers(
            $this->schoolAdmins((int) $toSchool->id),
            new StudentTransferRequestedNotification($transfer)
        );

        return $transfer;
    }

    public function acceptTransfer(StudentTransfer $transfer, User $acceptedBy): StudentTransfer
    {
        if ($transfer->status !== StudentTransfer::STATUS_PENDING) {
            throw new RuntimeException('Only pending transfers can be accepted.');
        }

        if ((int) ($acceptedBy->school_id ?? 0) !== (int) $transfer->to_school_id) {
            throw new RuntimeException('Only the receiving school can accept this transfer.');
        }

        DB::transaction(function () use ($transfer, $acceptedBy): void {
            $student = User::withoutGlobalScopes()->findOrFail($transfer->student_id);
            $fromSchoolId = (int) $transfer->from_school_id;
            $toSchoolId = (int) $transfer->to_school_id;

            if ((int) ($student->school_id ?? 0) !== $fromSchoolId) {
                throw new RuntimeException('Student school does not match transfer origin.');
            }

            User::withoutGlobalScopes()
                ->where('id', $student->id)
                ->update(['school_id' => $toSchoolId]);

            $studentRecordUpdate = ['school_id' => $toSchoolId];
            if ($transfer->from_class_id) {
                $studentRecordUpdate['my_class_id'] = (int) $transfer->from_class_id;
            }
            if ($transfer->from_section_id) {
                $studentRecordUpdate['section_id'] = (int) $transfer->from_section_id;
            }

            StudentRecord::withoutGlobalScopes()
                ->where('user_id', $student->id)
                ->update($studentRecordUpdate);

            Mark::withoutGlobalScopes()
                ->where('student_id', $student->id)
                ->where('school_id', $fromSchoolId)
                ->update(['school_id' => $toSchoolId]);

            ExamRecord::withoutGlobalScopes()
                ->where('student_id', $student->id)
                ->where('school_id', $fromSchoolId)
                ->update(['school_id' => $toSchoolId]);

            Promotion::withoutGlobalScopes()
                ->where('student_id', $student->id)
                ->where('school_id', $fromSchoolId)
                ->update(['school_id' => $toSchoolId]);

            $transfer->update([
                'status' => StudentTransfer::STATUS_ACCEPTED,
                'accepted_by' => (int) $acceptedBy->id,
                'transferred_at' => now(),
            ]);

            $this->appendTransferHistory($transfer, [
                'event' => 'accepted',
                'status' => StudentTransfer::STATUS_ACCEPTED,
                'actor_id' => (int) $acceptedBy->id,
                'actor_name' => (string) $acceptedBy->name,
                'actor_school_id' => (int) ($acceptedBy->school_id ?? 0),
            ]);

            StudentQrToken::updateOrCreate(
                ['student_id' => (int) $student->id],
                [
                    'school_id' => $toSchoolId,
                    'token' => $this->newQrToken(),
                ]
            );

            $this->syncBilledStudents($fromSchoolId);
            $this->syncBilledStudents($toSchoolId);
        });

        $transfer = $transfer->fresh(['student', 'fromSchool', 'toSchool', 'requestedBy', 'acceptedBy']);

        $student = User::withoutGlobalScopes()->find($transfer->student_id);
        $studentRecord = StudentRecord::withoutGlobalScopes()->where('user_id', $transfer->student_id)->first();
        $parent = $studentRecord && $studentRecord->my_parent_id
            ? User::withoutGlobalScopes()->find($studentRecord->my_parent_id)
            : null;

        $recipients = $this->schoolAdmins((int) $transfer->from_school_id)
            ->merge($student ? collect([$student]) : collect())
            ->merge($parent ? collect([$parent]) : collect())
            ->unique('id')
            ->values();

        $this->notifyUsers($recipients, new StudentTransferAcceptedNotification($transfer));

        return $transfer;
    }

    public function rejectTransfer(StudentTransfer $transfer, User $acceptedBy, string $reason): StudentTransfer
    {
        if ($transfer->status !== StudentTransfer::STATUS_PENDING) {
            throw new RuntimeException('Only pending transfers can be rejected.');
        }

        if ((int) ($acceptedBy->school_id ?? 0) !== (int) $transfer->to_school_id) {
            throw new RuntimeException('Only the receiving school can reject this transfer.');
        }

        $transfer->update([
            'status' => StudentTransfer::STATUS_REJECTED,
            'accepted_by' => (int) $acceptedBy->id,
            'rejected_reason' => $reason,
        ]);

        $this->appendTransferHistory($transfer, [
            'event' => 'rejected',
            'status' => StudentTransfer::STATUS_REJECTED,
            'actor_id' => (int) $acceptedBy->id,
            'actor_name' => (string) $acceptedBy->name,
            'actor_school_id' => (int) ($acceptedBy->school_id ?? 0),
            'reason' => $reason,
        ]);

        $transfer = $transfer->fresh(['student', 'fromSchool', 'toSchool', 'requestedBy', 'acceptedBy']);

        $student = User::withoutGlobalScopes()->find($transfer->student_id);
        $studentRecord = StudentRecord::withoutGlobalScopes()->where('user_id', $transfer->student_id)->first();
        $parent = $studentRecord && $studentRecord->my_parent_id
            ? User::withoutGlobalScopes()->find($studentRecord->my_parent_id)
            : null;

        $recipients = $this->schoolAdmins((int) $transfer->from_school_id)
            ->merge($student ? collect([$student]) : collect())
            ->merge($parent ? collect([$parent]) : collect())
            ->unique('id')
            ->values();

        $this->notifyUsers($recipients, new StudentTransferRejectedNotification($transfer));

        return $transfer;
    }

    public function cancelTransfer(StudentTransfer $transfer, User $requestedBy): StudentTransfer
    {
        if ($transfer->status !== StudentTransfer::STATUS_PENDING) {
            throw new RuntimeException('Only pending transfers can be cancelled.');
        }

        if ((int) ($requestedBy->school_id ?? 0) !== (int) $transfer->from_school_id) {
            throw new RuntimeException('Only the sending school can cancel this transfer.');
        }

        $transfer->update([
            'status' => StudentTransfer::STATUS_CANCELLED,
        ]);

        $this->appendTransferHistory($transfer, [
            'event' => 'cancelled',
            'status' => StudentTransfer::STATUS_CANCELLED,
            'actor_id' => (int) $requestedBy->id,
            'actor_name' => (string) $requestedBy->name,
            'actor_school_id' => (int) ($requestedBy->school_id ?? 0),
        ]);

        return $transfer->fresh(['student', 'fromSchool', 'toSchool', 'requestedBy', 'acceptedBy']);
    }

    private function syncBilledStudents(int $schoolId): void
    {
        $school = School::query()
            ->with(['subscription', 'billingPlan'])
            ->find($schoolId);

        if (!$school || !$school->subscription) {
            return;
        }

        $billable = $school->billableStudentCount();

        SchoolSubscription::query()
            ->where('id', $school->subscription->id)
            ->update(['billed_students' => $billable]);
    }

    private function newQrToken(): string
    {
        do {
            $token = Str::lower(Str::random(64));
            $exists = StudentQrToken::query()->where('token', $token)->exists();
        } while ($exists);

        return $token;
    }

    private function schoolAdmins(int $schoolId): Collection
    {
        return User::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->whereIn('user_type', ['super_admin', 'admin'])
            ->get();
    }

    private function notifyUsers(Collection $users, $notification): void
    {
        foreach ($users as $user) {
            if ($user && $user->email) {
                $user->notify($notification);
            }
        }
    }

    private function appendTransferHistory(StudentTransfer $transfer, array $entry): void
    {
        $history = $transfer->status_history;
        if (!is_array($history)) {
            $history = [];
        }

        $entry['at'] = $entry['at'] ?? now()->toDateTimeString();
        $history[] = $entry;

        $transfer->status_history = $history;
        $transfer->save();
    }

    private function buildTransferSnapshot(User $student, ?StudentRecord $record, ?User $parent, School $toSchool, User $requestedBy): array
    {
        $fromSchool = School::query()->find((int) ($student->school_id ?? 0));

        return [
            'student' => [
                'id' => (int) $student->id,
                'name' => (string) $student->name,
                'code' => (string) $student->code,
                'email' => (string) ($student->email ?? ''),
                'phone' => (string) ($student->phone ?? ''),
                'photo' => (string) ($student->photo ?? ''),
                'gender' => (string) ($student->gender ?? ''),
                'dob' => (string) ($student->dob ?? ''),
                'address' => (string) ($student->address ?? ''),
            ],
            'parent' => [
                'id' => $parent ? (int) $parent->id : null,
                'name' => (string) ($parent->name ?? ''),
                'phone' => (string) ($parent->phone ?? ''),
                'phone2' => (string) ($parent->phone2 ?? ''),
                'email' => (string) ($parent->email ?? ''),
            ],
            'academic' => [
                'student_record_id' => $record ? (int) $record->id : null,
                'admission_no' => (string) ($record->adm_no ?? ''),
                'session' => (string) ($record->session ?? ''),
                'class_id' => $record ? (int) $record->my_class_id : null,
                'class_name' => (string) optional($record ? $record->my_class : null)->name,
                'section_id' => $record ? (int) $record->section_id : null,
                'section_name' => (string) optional($record ? $record->section : null)->name,
                'year_admitted' => (string) ($record->year_admitted ?? ''),
            ],
            'schools' => [
                'from' => [
                    'id' => $fromSchool ? (int) $fromSchool->id : null,
                    'name' => (string) ($fromSchool->name ?? ''),
                    'email' => (string) ($fromSchool->email ?? ''),
                    'phone' => (string) ($fromSchool->phone ?? ''),
                ],
                'to' => [
                    'id' => (int) $toSchool->id,
                    'name' => (string) $toSchool->name,
                    'email' => (string) ($toSchool->email ?? ''),
                    'phone' => (string) ($toSchool->phone ?? ''),
                ],
            ],
            'requested_by' => [
                'id' => (int) $requestedBy->id,
                'name' => (string) $requestedBy->name,
            ],
            'captured_at' => now()->toDateTimeString(),
        ];
    }
}
