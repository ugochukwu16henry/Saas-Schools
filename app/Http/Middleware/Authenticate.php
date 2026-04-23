<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Support\Str;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function redirectTo($request)
    {
        if ($request->expectsJson()) {
            return null;
        }

        $name = $request->route() ? $request->route()->getName() : '';
        if (Str::startsWith($name, 'platform.')) {
            return route('platform.login');
        }
        if (Str::startsWith($name, 'affiliate.')) {
            return route('affiliate.login');
        }

        return route('login');
    }
}
