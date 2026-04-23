<?php

namespace App\Http\Controllers\Ai;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;

class AnnouncementPageController extends Controller
{
    public function index()
    {
        if (! Qs::userIsTeamSA()) {
            abort(403, 'You are not allowed to access AI tools.');
        }

        return view('pages.support_team.ai.announcement');
    }
}
