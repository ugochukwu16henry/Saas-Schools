<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Enforce payment-failure grace windows and auto-suspend delinquent schools.
        $schedule->command('billing:enforce-dunning')
            ->hourly()
            ->withoutOverlapping()
            ->onOneServer();

        // Expire overdue trials and suspend schools that never converted.
        $schedule->command('billing:expire-trials')
            ->hourly()
            ->withoutOverlapping()
            ->onOneServer();

        if ((bool) config('platform.digest.enabled', false)) {
            $frequency = strtolower((string) config('platform.digest.frequency', 'daily'));
            $time = (string) config('platform.digest.time', '08:00');

            if ($frequency === 'weekly') {
                $dayMap = [
                    'sunday' => 0,
                    'monday' => 1,
                    'tuesday' => 2,
                    'wednesday' => 3,
                    'thursday' => 4,
                    'friday' => 5,
                    'saturday' => 6,
                ];
                $weeklyDay = strtolower((string) config('platform.digest.weekly_day', 'monday'));
                $dayIndex = $dayMap[$weeklyDay] ?? 1;

                $schedule->command('platform:send-digest --period=auto')
                    ->weeklyOn($dayIndex, $time)
                    ->withoutOverlapping()
                    ->onOneServer();
            } else {
                $schedule->command('platform:send-digest --period=auto')
                    ->dailyAt($time)
                    ->withoutOverlapping()
                    ->onOneServer();
            }
        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
