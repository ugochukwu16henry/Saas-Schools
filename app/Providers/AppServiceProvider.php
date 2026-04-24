<?php

namespace App\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->environment('production')) {
            $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
            $sessionDomain = (string) config('session.domain');

            if ($appHost && $sessionDomain !== '') {
                $normalizedSessionDomain = ltrim(trim($sessionDomain), '.');
                $matchesExactly = $appHost === $normalizedSessionDomain;
                $matchesSubdomain = Str::endsWith($appHost, '.' . $normalizedSessionDomain);

                if (!$matchesExactly && !$matchesSubdomain) {
                    Log::warning('Session domain does not match APP_URL host. This may break login sessions and CSRF.', [
                        'app_url' => config('app.url'),
                        'app_host' => $appHost,
                        'session_domain' => $sessionDomain,
                    ]);
                }
            }
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->isLocal() && class_exists(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class)) {
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        }
        //
    }
}
