<?php

namespace App\Http\Middleware\Custom;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckAbility
{
    /**
     * Handle an incoming request.
     */
    public function handle($request, Closure $next, string $ability)
    {
        $actor = $this->resolveActorType($request);
        if ($actor === null) {
            Log::warning('Ability check failed: unauthenticated actor', [
                'ability' => $ability,
                'path' => $request->path(),
                'method' => $request->method(),
                'ip' => $request->ip(),
            ]);

            return redirect()->route('login');
        }

        $allowedActors = (array) config('permissions.abilities.' . $ability, []);
        if (in_array('*', $allowedActors, true) || in_array($actor, $allowedActors, true)) {
            return $next($request);
        }

        Log::warning('Ability check denied', [
            'ability' => $ability,
            'actor_type' => $actor,
            'user_id' => Auth::id(),
            'path' => $request->path(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'allowed_actors' => $allowedActors,
        ]);

        abort(403, 'You are not allowed to perform this action.');
    }

    private function resolveActorType($request): ?string
    {
        $guardHint = $this->resolveGuardHint($request);

        if ($guardHint === 'platform') {
            return Auth::guard('platform')->check() ? 'platform_admin' : null;
        }

        if ($guardHint === 'affiliate') {
            return Auth::guard('affiliate')->check() ? 'affiliate' : null;
        }

        if (Auth::check()) {
            $user = Auth::user();

            return (string) ($user->user_type ?? 'user');
        }

        if (Auth::guard('platform')->check()) {
            return 'platform_admin';
        }

        if (Auth::guard('affiliate')->check()) {
            return 'affiliate';
        }

        return null;
    }

    private function resolveGuardHint($request): ?string
    {
        $route = $request->route();
        if (!$route) {
            return null;
        }

        foreach ((array) $route->gatherMiddleware() as $middleware) {
            if (strpos($middleware, 'auth:platform') === 0) {
                return 'platform';
            }

            if (strpos($middleware, 'auth:affiliate') === 0) {
                return 'affiliate';
            }
        }

        return null;
    }
}
