<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReferralRedirectController extends Controller
{
    public function __invoke(Request $request, string $code)
    {
        $request->session()->put('school_registration_ref', strtoupper(trim($code)));

        return redirect()->route('school.register', ['ref' => strtoupper(trim($code))]);
    }
}
