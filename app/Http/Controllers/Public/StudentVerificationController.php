<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\StudentQrService;

class StudentVerificationController extends Controller
{
    public function show(string $token, StudentQrService $qrService)
    {
        return redirect()->route('students.verify.proof', $token);
    }

    public function proof(string $token, StudentQrService $qrService)
    {
        $payload = $this->resolveVerificationPayload($token, $qrService);
        if (!$payload) {
            return response()->view('public.student_proof', [
                'verified' => false,
                'message' => 'Student record could not be verified with this QR token.',
                'token' => $token,
            ], 404);
        }

        return view('public.student_proof', $payload);
    }

    private function resolveVerificationPayload(string $token, StudentQrService $qrService): ?array
    {
        $qrToken = $qrService->findByToken($token);
        if (!$qrToken || !$qrToken->student) {
            return null;
        }

        $student = $qrToken->student;
        $record = $student->student_record;
        $currentSchool = $student->school ?: $qrToken->school;

        return [
            'verified' => true,
            'student' => $student,
            'record' => $record,
            'school' => $currentSchool,
            'token' => $qrToken->token,
            'verificationUrl' => route('students.verify.proof', $qrToken->token),
        ];
    }
}
