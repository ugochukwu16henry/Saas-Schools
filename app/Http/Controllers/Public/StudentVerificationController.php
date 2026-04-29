<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Setting;
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
        $schoolId = (int) (optional($currentSchool)->id ?: (int) ($qrToken->school_id ?? 0));

        $settings = collect();
        if ($schoolId > 0) {
            $settings = Setting::withoutGlobalScopes()
                ->where('school_id', $schoolId)
                ->whereIn('type', ['system_name', 'system_email', 'phone', 'address', 'logo'])
                ->pluck('description', 'type');
        }

        $schoolProfile = [
            'name' => trim((string) ($settings->get('system_name') ?? optional($currentSchool)->name ?? '')),
            'email' => trim((string) ($settings->get('system_email') ?? optional($currentSchool)->email ?? '')),
            'phone' => trim((string) ($settings->get('phone') ?? optional($currentSchool)->phone ?? '')),
            'address' => trim((string) ($settings->get('address') ?? optional($currentSchool)->address ?? '')),
            'logo' => trim((string) ($settings->get('logo') ?? optional($currentSchool)->logo ?? '')),
        ];

        $studentStatus = (optional($record)->grad ?? 0) ? 'Graduated' : 'Active Student';

        return [
            'verified' => true,
            'student' => $student,
            'record' => $record,
            'school' => $currentSchool,
            'schoolProfile' => $schoolProfile,
            'studentStatus' => $studentStatus,
            'token' => $qrToken->token,
            'verificationUrl' => route('students.verify.proof', $qrToken->token),
        ];
    }
}
