<?php

namespace App\Console\Commands;

use App\Models\SchoolSubscription;
use App\Services\BillingDunningNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EnforceDunning extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:enforce-dunning {--dry-run : Report actions without updating records}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enforce billing dunning policy using failure counters and grace period windows.';

    public function handle(): int
    {
        $now = now();
        $dryRun = (bool) $this->option('dry-run');
        $threshold = max(1, (int) config('paystack.payment_failure_threshold', 3));
        $notifier = app(BillingDunningNotificationService::class);

        $processed = 0;
        $warnings = 0;
        $suspended = 0;

        SchoolSubscription::query()
            ->with('school')
            ->where('payment_failures_count', '>', 0)
            ->orderByDesc('last_payment_failed_at')
            ->chunkById(100, function ($subs) use ($now, $threshold, $dryRun, $notifier, &$processed, &$warnings, &$suspended) {
                foreach ($subs as $sub) {
                    $processed++;

                    $school = $sub->school;
                    if (! $school) {
                        continue;
                    }

                    $graceEndsAt = $sub->grace_period_ends_at ? Carbon::parse($sub->grace_period_ends_at) : null;
                    $failures = (int) $sub->payment_failures_count;

                    $shouldSuspend = $failures >= $threshold;
                    if (! $shouldSuspend && $graceEndsAt) {
                        $shouldSuspend = $graceEndsAt->lte($now);
                    }

                    if ($shouldSuspend) {
                        if (! $dryRun) {
                            $wasAlreadySuspended = $school->status === 'suspended';

                            $sub->status = 'expired';
                            if (! $sub->grace_period_ends_at) {
                                $sub->grace_period_ends_at = $now;
                            }
                            $sub->save();

                            $school->status = 'suspended';
                            $school->save();

                            if (! $wasAlreadySuspended) {
                                $notifier->sendSuspensionNotice($school, $sub);
                            }
                        }

                        $suspended++;

                        Log::warning('Dunning enforcement suspended school due to payment failures.', [
                            'school_id' => $school->id,
                            'subscription_id' => $sub->id,
                            'failures' => $failures,
                            'threshold' => $threshold,
                            'grace_ends_at' => optional($graceEndsAt)->toDateTimeString(),
                            'dry_run' => $dryRun,
                        ]);

                        continue;
                    }

                    $warnings++;

                    // Send reminders only near the failure event window to avoid hourly spam.
                    $shouldNotifyWarning = false;
                    if (! $dryRun && $sub->last_payment_failed_at) {
                        $failedAt = Carbon::parse($sub->last_payment_failed_at);
                        $shouldNotifyWarning = $failedAt->gte($now->copy()->subMinutes(70));
                    }

                    if ($shouldNotifyWarning) {
                        $notifier->sendPaymentFailureWarning($school, $sub);
                    }

                    Log::info('Dunning enforcement reminder window active.', [
                        'school_id' => $school->id,
                        'subscription_id' => $sub->id,
                        'failures' => $failures,
                        'threshold' => $threshold,
                        'grace_ends_at' => optional($graceEndsAt)->toDateTimeString(),
                        'dry_run' => $dryRun,
                    ]);
                }
            });

        $this->info(sprintf(
            'Dunning scan complete. Processed: %d | Reminder window: %d | Suspended: %d | Dry-run: %s',
            $processed,
            $warnings,
            $suspended,
            $dryRun ? 'yes' : 'no'
        ));

        return self::SUCCESS;
    }
}
