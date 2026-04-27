<?php

namespace App\Http\Middleware\Custom;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckAbility
{
    /**
     * Handle an incoming request.
     */
    public function handle($request, Closure $next, string $ability)
    {
        $actor = $this->resolveActorType();
        if ($actor === null) {
            return redirect()->route('login');
        }

        $allowedActors = (array) config('permissions.abilities.' . $ability, []);
        if (in_array('*', $allowedActors, true) || in_array($actor, $allowedActors, true)) {
            return $next($request);
        }

        abort(403, 'You are not allowed to perform this action.');
    }

    private function resolveActorType(): ?string
    {
        if (Auth::guard('platform')->check()) {
            return 'platform_admin';
        }

        if (Auth::guard('affiliate')->check()) {
            return 'affiliate';
        }

        if (Auth::check()) {
            $user = Auth::user();

            return (string) ($user->user_type ?? 'user');
        }

        return null;
    }
}
