<?php

namespace App\Providers;

use App\Models\SchoolSubscription;
use Illuminate\Support\Facades\Auth;
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
        view()->composer('*', function ($view) {
            $banner = null;

            try {
                if (!Auth::check() || !app()->bound('currentSchool')) {
                    $view->with('dunningBanner', $banner);
                    return;
                }

                $userType = (string) optional(Auth::user())->user_type;
                if (!in_array($userType, ['super_admin', 'admin'], true)) {
                    $view->with('dunningBanner', $banner);
                    return;
                }

                $school = app('currentSchool');
                $sub = SchoolSubscription::where('school_id', $school->id)->first();

                if (!$sub) {
                    $view->with('dunningBanner', $banner);
                    return;
                }

                $failures = (int) ($sub->payment_failures_count ?? 0);
                $graceEnds = optional($sub->grace_period_ends_at)->format('d M Y H:i');

                if ($failures <= 0 && !in_array((string) $sub->status, ['expired', 'cancelled'], true)) {
                    $view->with('dunningBanner', $banner);
                    return;
                }

                if (in_array((string) $sub->status, ['expired', 'cancelled'], true) || $school->status === 'suspended') {
                    $banner = [
                        'level' => 'danger',
                        'title' => 'Billing access restricted',
                        'message' => 'Your account is currently suspended due to unpaid billing. Please settle payment to restore full access.',
                        'meta' => $graceEnds ? 'Last grace deadline: ' . $graceEnds : null,
                    ];
                } else {
                    $banner = [
                        'level' => 'warning',
                        'title' => 'Billing payment pending',
                        'message' => 'Recent billing payment attempts failed. Please resolve billing to avoid suspension.',
                        'meta' => $graceEnds ? ('Grace period ends: ' . $graceEnds . ' | Failed attempts: ' . $failures) : ('Failed attempts: ' . $failures),
                    ];
                }
            } catch (\Throwable $e) {
                $banner = null;
            }

            $view->with('dunningBanner', $banner);
        });

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
        if ($this->app->environment('local') && class_exists(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class)) {
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        }
        //
    }
}
