<?php

namespace App\Services;

use App\Models\StudentQrToken;
use App\User;
use Illuminate\Support\Str;

class StudentQrService
{
    public function ensureTokenForStudent(User $student): StudentQrToken
    {
        return StudentQrToken::updateOrCreate(
            ['student_id' => (int) $student->id],
            [
                'school_id' => (int) ($student->school_id ?? 0),
                'token' => $this->newTokenForStudent((int) $student->id),
            ]
        );
    }

    public function findByToken(string $token): ?StudentQrToken
    {
        return StudentQrToken::query()
            ->with(['student.school', 'student.student_record.my_class', 'student.student_record.section', 'school'])
            ->where('token', trim($token))
            ->first();
    }

    private function newTokenForStudent(int $studentId): string
    {
        $existing = StudentQrToken::query()->where('student_id', $studentId)->first();
        if ($existing && $existing->token) {
            return (string) $existing->token;
        }

        do {
            $token = Str::lower(Str::random(64));
            $exists = StudentQrToken::query()->where('token', $token)->exists();
        } while ($exists);

        return $token;
    }
}
