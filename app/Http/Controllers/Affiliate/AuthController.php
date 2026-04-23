<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest:affiliate')->only(['showLogin', 'login']);
        $this->middleware('auth:affiliate')->only(['logout']);
    }

    public function showLogin()
    {
        return view('affiliate.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (! Auth::guard('affiliate')->attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->withInput($request->only('email'));
        }

        $affiliate = Auth::guard('affiliate')->user();
        if ($affiliate->status !== 'approved') {
            Auth::guard('affiliate')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['email' => 'Your affiliate account is not approved yet.'])->withInput($request->only('email'));
        }

        $request->session()->regenerate();

        return redirect()->route('affiliate.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::guard('affiliate')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('affiliate.login')->with('status', 'You have been logged out.');
    }
}
