<?php

namespace App\Http\Controllers\Auth;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void$field
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /*
     *  Login with Username or Email
     * */
    public function username()
    {
        $identity = trim((string) request()->input('identity', request()->input('email', request()->input('username', ''))));
        $field = filter_var($identity, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        request()->merge([$field => $identity]);
        return $field;
    }

    /**
     * Allow login form identity from identity/email/username fields and trim whitespace.
     */
    protected function credentials(Request $request)
    {
        $identity = trim((string) $request->input('identity', $request->input('email', $request->input('username', ''))));
        $request->merge(['identity' => $identity]);

        $field = filter_var($identity, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        return [
            $field => $identity,
            'password' => (string) $request->input('password'),
        ];
    }

    /**
     * Try common identity patterns so users can sign in with email/username/local-part.
     */
    protected function attemptLogin(Request $request)
    {
        $password = (string) $request->input('password');
        $identity = trim((string) $request->input('identity', ''));

        $attempts = [];
        if ($identity !== '') {
            $attempts[] = ['email' => $identity, 'password' => $password];
            $attempts[] = ['username' => $identity, 'password' => $password];

            // Accept "admin" when account email is "admin@domain.tld".
            if (strpos($identity, '@') === false) {
                $matchedEmail = User::withoutGlobalScopes()
                    ->whereRaw('LOWER(SUBSTRING_INDEX(email, "@", 1)) = ?', [strtolower($identity)])
                    ->value('email');

                if ($matchedEmail) {
                    $attempts[] = ['email' => $matchedEmail, 'password' => $password];
                }
            }
        }

        foreach ($attempts as $credentials) {
            if ($this->guard()->attempt($credentials, $request->filled('remember'))) {
                return true;
            }
        }

        return false;
    }
}
