<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Services\StudentQrService;
use App\Services\StudentTranscriptService;
use App\User;
use Illuminate\Http\Request;
use PDF;

class StudentTranscriptController extends Controller
{
    public function show(User $student, StudentTranscriptService $transcriptService, StudentQrService $qrService)
    {
        $this->authorizeView($student);

        $data = $transcriptService->build($student);
        $token = $qrService->ensureTokenForStudent($student)->token;
        $data['verifyUrl'] = route('students.verify.proof', $token);

        return view('pages.support_team.students.transcript', $data);
    }

    public function download(User $student, StudentTranscriptService $transcriptService, StudentQrService $qrService)
    {
        $this->authorizeView($student);

        $data = $transcriptService->build($student);
        $token = $qrService->ensureTokenForStudent($student)->token;
        $data['verifyUrl'] = route('students.verify.proof', $token);

        $filename = 'Transcript_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) $student->name) . '.pdf';

        return PDF::loadView('pages.support_team.students.transcript_pdf', $data)->download($filename);
    }

    private function authorizeView(User $student): void
    {
        $viewer = auth()->user();

        if (!$viewer) {
            abort(403, __('msg.denied'));
        }

        if (Qs::userIsTeamSAT()) {
            return;
        }

        if (Qs::userIsStudent() && (int) $viewer->id === (int) $student->id) {
            return;
        }

        if (Qs::userIsParent() && Qs::userIsMyChild((int) $student->id, (int) $viewer->id)) {
            return;
        }

        abort(403, __('msg.denied'));
    }
}
