<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\StudentQrService;

class StudentVerificationController extends Controller
{
    public function show(string $token, StudentQrService $qrService)
    {
        $qrToken = $qrService->findByToken($token);

        if (!$qrToken || !$qrToken->student) {
            return response()->view('public.student_verification', [
                'verified' => false,
                'message' => 'Student record could not be verified with this QR token.',
            ], 404);
        }

        $student = $qrToken->student;
        $record = $student->student_record;
        $currentSchool = $student->school ?: $qrToken->school;

        return view('public.student_verification', [
            'verified' => true,
            'student' => $student,
            'record' => $record,
            'school' => $currentSchool,
            'token' => $qrToken->token,
            'verificationUrl' => route('students.verify.public', $qrToken->token),
        ]);
    }
}
