<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('platform.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::guard('platform')->attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Invalid platform admin credentials.'])->withInput($request->only('email'));
        }

        $request->session()->regenerate();

        return redirect()->route('platform.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::guard('platform')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('platform.login')->with('status', 'You have logged out successfully.');
    }
}
