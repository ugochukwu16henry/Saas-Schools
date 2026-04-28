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
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class StudentTransferService
{
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

        return StudentTransfer::create([
            'student_id' => (int) $student->id,
            'from_school_id' => $fromSchoolId,
            'to_school_id' => (int) $toSchool->id,
            'requested_by' => (int) $requestedBy->id,
            'status' => StudentTransfer::STATUS_PENDING,
            'from_class_id' => $record ? (int) $record->my_class_id : null,
            'from_section_id' => $record ? (int) $record->section_id : null,
            'from_session' => $record ? (string) $record->session : null,
            'transfer_note' => $note,
        ]);
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

            StudentRecord::withoutGlobalScopes()
                ->where('user_id', $student->id)
                ->update(['school_id' => $toSchoolId]);

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

        return $transfer->fresh(['student', 'fromSchool', 'toSchool', 'requestedBy', 'acceptedBy']);
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

        return $transfer->fresh(['student', 'fromSchool', 'toSchool', 'requestedBy', 'acceptedBy']);
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
}
