<?php

namespace App\Console\Commands;

use App\Models\SchoolSubscription;
use App\Services\BillingDunningNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpireTrials extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:expire-trials {--dry-run : Report actions without updating records}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire overdue trial subscriptions and suspend affected schools.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $now = now();
        $notifier = app(BillingDunningNotificationService::class);

        $processed = 0;
        $expired = 0;
        $warnings = 0;

        SchoolSubscription::query()
            ->with('school')
            ->where('status', 'trialling')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', $now)
            ->orderBy('trial_ends_at')
            ->chunkById(100, function ($subs) use ($dryRun, $now, $notifier, &$processed, &$warnings) {
                foreach ($subs as $sub) {
                    $processed++;

                    $school = $sub->school;
                    if (! $school) {
                        continue;
                    }

                    $trialEndsAt = Carbon::parse($sub->trial_ends_at);
                    $daysRemaining = $now->diffInDays($trialEndsAt, false);

                    if ($daysRemaining === 7 && !$sub->trial_warning_7d_sent_at) {
                        if (! $dryRun) {
                            $notifier->sendTrialExpiringWarning($school, $sub, 7);
                            $sub->trial_warning_7d_sent_at = now();
                            $sub->save();
                        }

                        $warnings++;

                        Log::info('Trial warning (7 days) processed.', [
                            'school_id' => $school->id,
                            'subscription_id' => $sub->id,
                            'trial_ends_at' => $trialEndsAt->toDateTimeString(),
                            'dry_run' => $dryRun,
                        ]);

                        continue;
                    }

                    if ($daysRemaining <= 1 && !$sub->trial_warning_1d_sent_at) {
                        if (! $dryRun) {
                            $notifier->sendTrialExpiringWarning($school, $sub, max(0, $daysRemaining));
                            $sub->trial_warning_1d_sent_at = now();
                            $sub->save();
                        }

                        $warnings++;

                        Log::info('Trial warning (1 day window) processed.', [
                            'school_id' => $school->id,
                            'subscription_id' => $sub->id,
                            'trial_ends_at' => $trialEndsAt->toDateTimeString(),
                            'days_remaining' => $daysRemaining,
                            'dry_run' => $dryRun,
                        ]);
                    }
                }
            });

        SchoolSubscription::query()
            ->with('school')
            ->where('status', 'trialling')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', $now)
            ->orderBy('trial_ends_at')
            ->chunkById(100, function ($subs) use ($dryRun, &$processed, &$expired) {
                foreach ($subs as $sub) {
                    $processed++;

                    $school = $sub->school;
                    if (! $school) {
                        continue;
                    }

                    $wasAlreadyExpired = $sub->status === 'expired';
                    if ($wasAlreadyExpired) {
                        continue;
                    }

                    if (! $dryRun) {
                        $sub->status = 'expired';
                        $sub->save();

                        $school->status = 'suspended';
                        $school->save();
                    }

                    $expired++;

                    Log::warning('Trial expired and school suspended.', [
                        'school_id' => $school->id,
                        'subscription_id' => $sub->id,
                        'trial_ends_at' => optional($sub->trial_ends_at)->toDateTimeString(),
                        'dry_run' => $dryRun,
                    ]);
                }
            });

        $this->info(sprintf(
            'Trial expiry scan complete. Processed: %d | Warnings: %d | Expired: %d | Dry-run: %s',
            $processed,
            $warnings,
            $expired,
            $dryRun ? 'yes' : 'no'
        ));

        return self::SUCCESS;
    }
}
