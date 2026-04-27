<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolSubscription;
use App\Services\AffiliateCommissionService;
use App\Services\BillingDunningNotificationService;
use App\Services\PlatformNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackController extends Controller
{
    private string $secretKey;
    private string $baseUrl;
    private BillingDunningNotificationService $dunningNotifier;

    public function __construct(BillingDunningNotificationService $dunningNotifier)
    {
        $this->dunningNotifier = $dunningNotifier;
        $this->secretKey = config('paystack.secret_key', '');
        $this->baseUrl   = config('paystack.payment_url', 'https://api.paystack.co');
    }

    /**
     * Show the billing required prompt page.
     */
    public function prompt()
    {
        $school = app('currentSchool')->loadMissing('billingPlan');
        $studentCount = $school->users()->where('user_type', 'student')->count();
        $billing = $this->buildBillingSummary($school, $studentCount);

        return view('billing.prompt', array_merge(
            compact('school', 'studentCount'),
            $billing,
            [
                'monthlyRate' => $billing['monthlyRate'],
                'oneTimeRate' => $billing['oneTimeRate'],
                'freeLimit' => $billing['freeLimit'],
                'planName' => $billing['planName'],
            ]
        ));
    }

    /**
     * Initialize a Paystack transaction and redirect to payment page.
     */
    public function initialize(Request $request)
    {
        $school = app('currentSchool')->loadMissing('billingPlan');
        $user   = auth()->user();

        $studentCount  = $school->users()->where('user_type', 'student')->count();
        $billing       = $this->buildBillingSummary($school, $studentCount);
        $amountNaira   = $billing['totalDue'];
        $amountKobo    = $amountNaira * 100;

        if ($amountKobo <= 0) {
            return redirect()->route('dashboard')->with('status', 'No payment needed right now.');
        }

        $response = Http::withToken($this->secretKey)
            ->post("{$this->baseUrl}/transaction/initialize", [
                'email'        => $user->email,
                'amount'       => $amountKobo,
                'callback_url' => route('billing.callback'),
                'metadata'     => [
                    'school_id'      => $school->id,
                    'billable_students' => $billing['billableCount'],
                    'newly_added_students' => $billing['newlyAddedCount'],
                    'monthly_amount' => $billing['monthlyAmount'],
                    'one_time_amount' => $billing['oneTimeAmount'],
                    'total_due' => $billing['totalDue'],
                    'monthly_rate' => $billing['monthlyRate'],
                    'one_time_rate' => $billing['oneTimeRate'],
                    'free_limit' => $billing['freeLimit'],
                    'billing_plan_id' => $school->billing_plan_id,
                    'cancel_action'  => route('billing.prompt'),
                ],
            ]);

        if (!$response->successful() || !data_get($response->json(), 'status')) {
            Log::error('Paystack initialize failed', $response->json());
            return back()->withErrors(['billing' => 'Could not initialize payment. Please try again.']);
        }

        $authorizationUrl = data_get($response->json(), 'data.authorization_url');

        return redirect()->away($authorizationUrl);
    }

    /**
     * Handle Paystack payment callback after user pays.
     */
    public function callback(Request $request)
    {
        $reference = $request->query('reference');
        if (!$reference) {
            return redirect()->route('billing.prompt')->withErrors(['billing' => 'Invalid payment reference.']);
        }

        $response = Http::withToken($this->secretKey)
            ->get("{$this->baseUrl}/transaction/verify/{$reference}");

        if (!$response->successful()) {
            return redirect()->route('billing.prompt')->withErrors(['billing' => 'Payment verification failed.']);
        }

        $data   = $response->json('data');
        $status = data_get($data, 'status');

        if ($status !== 'success') {
            if (is_array($data)) {
                $this->applyFailedPaymentFromPaystack($data, 'Payment verification returned non-success status.');
            }
            return redirect()->route('billing.prompt')->withErrors(['billing' => 'Payment was not successful.']);
        }

        $this->applySuccessfulPaymentFromPaystack($data);

        return redirect()->route('dashboard')->with('status', 'Payment successful! Billing has been updated.');
    }

    /**
     * Handle Paystack webhook events.
     * Route must be CSRF-exempt (add to VerifyCsrfToken $except).
     */
    public function webhook(Request $request)
    {
        $signature = $request->header('X-Paystack-Signature');
        $payload   = $request->getContent();

        if (!$signature) {
            abort(401, 'Missing webhook signature');
        }

        if (!hash_equals(hash_hmac('sha512', $payload, $this->secretKey), $signature)) {
            abort(401, 'Invalid webhook signature');
        }

        $event = $request->input('event');
        $data  = (array) $request->input('data', []);

        match ($event) {
            'charge.success'           => $this->handleChargeSuccess($data),
            'charge.failed'            => $this->handleChargeFailed($data),
            'invoice.payment_failed'   => $this->handleInvoicePaymentFailed($data),
            'subscription.disable'     => $this->handleSubscriptionDisabled($data),
            default                    => null,
        };

        return response()->json(['status' => 'ok']);
    }

    private function handleChargeSuccess(array $data): void
    {
        $this->applySuccessfulPaymentFromPaystack($data);
    }

    private function handleChargeFailed(array $data): void
    {
        $this->applyFailedPaymentFromPaystack($data, 'Paystack charge.failed event received.');
    }

    private function handleInvoicePaymentFailed(array $data): void
    {
        $this->applyFailedPaymentFromPaystack($data, 'Paystack invoice.payment_failed event received.');
    }

    /**
     * Single path for successful Paystack charges (browser callback + webhook).
     */
    private function applySuccessfulPaymentFromPaystack(array $data): void
    {
        $school = $this->resolveSchoolFromPaystackPayload($data);
        if (! $school) {
            return;
        }

        $billableStudents = (int) data_get($data, 'metadata.billable_students', 0);
        $newlyAddedCount = (int) data_get($data, 'metadata.newly_added_students', 0);
        $existingSub = SchoolSubscription::where('school_id', $school->id)->first();
        $alreadyPaidOneTime = (int) optional($existingSub)->billed_students;
        $updatedPaidBaseline = max($alreadyPaidOneTime, $alreadyPaidOneTime + $newlyAddedCount, $billableStudents);

        SchoolSubscription::updateOrCreate(
            ['school_id' => $school->id],
            [
                'status' => 'active',
                'billed_students' => $updatedPaidBaseline,
                'next_payment_date' => now()->addMonth(),
                'payment_failures_count' => 0,
                'last_payment_failed_at' => null,
                'last_payment_failure_reason' => null,
                'grace_period_ends_at' => null,
                'last_payment_reference' => (string) data_get($data, 'reference', ''),
                'paystack_subscription_code' => data_get($data, 'subscription.subscription_code', ''),
                'paystack_customer_code' => data_get($data, 'customer.customer_code', ''),
            ]
        );

        $school->update(['status' => 'active']);

        app(AffiliateCommissionService::class)->recordFromPaystackCharge($data);

        Log::info("Paystack charge.success: school {$school->id}");
    }

    private function applyFailedPaymentFromPaystack(array $data, ?string $defaultReason = null): void
    {
        $school = $this->resolveSchoolFromPaystackPayload($data);
        if (! $school) {
            Log::warning('Paystack payment failure received without resolvable school.', [
                'reference' => data_get($data, 'reference'),
                'event_reason' => $defaultReason,
            ]);
            return;
        }

        $sub = SchoolSubscription::firstOrCreate(
            ['school_id' => $school->id],
            ['status' => 'trialling']
        );

        $failureReason = (string) (data_get($data, 'gateway_response')
            ?: data_get($data, 'status')
            ?: $defaultReason
            ?: 'Payment failed');

        $failureCount = (int) $sub->payment_failures_count + 1;

        $updates = [
            'payment_failures_count' => $failureCount,
            'last_payment_failed_at' => now(),
            'last_payment_failure_reason' => $failureReason,
            'last_payment_reference' => (string) data_get($data, 'reference', ''),
        ];

        if ($failureCount >= $this->paymentFailureThreshold()) {
            $wasAlreadySuspended = $school->status === 'suspended';

            $updates['status'] = 'expired';
            $updates['grace_period_ends_at'] = now();
            $school->update(['status' => 'suspended']);

            if (! $wasAlreadySuspended) {
                $this->dunningNotifier->sendSuspensionNotice($school, $sub->fill($updates));
                app(PlatformNotificationService::class)->schoolSuspendedForBilling($school, $failureCount);
            }
        } else {
            $updates['grace_period_ends_at'] = now()->addDays($this->paymentFailureGraceDays());

            $this->dunningNotifier->sendPaymentFailureWarning($school, $sub->fill($updates));
            app(PlatformNotificationService::class)->paymentFailure($school, $failureCount, $failureReason);
        }

        $sub->update($updates);

        Log::warning('Paystack payment failure recorded.', [
            'school_id' => $school->id,
            'failure_count' => $failureCount,
            'failure_threshold' => $this->paymentFailureThreshold(),
            'reference' => data_get($data, 'reference'),
        ]);
    }

    private function handleSubscriptionDisabled(array $data): void
    {
        $school = $this->resolveSchoolFromPaystackPayload($data);
        if (!$school) {
            return;
        }

        $wasAlreadySuspended = $school->status === 'suspended';

        $sub = SchoolSubscription::where('school_id', $school->id)->first();
        if ($sub) {
            $sub->update(['status' => 'cancelled']);
        }

        $school->update(['status' => 'suspended']);

        if ($sub && ! $wasAlreadySuspended) {
            $this->dunningNotifier->sendSuspensionNotice($school, $sub);
            app(PlatformNotificationService::class)->schoolSuspendedForBilling(
                $school,
                (int) ($sub->payment_failures_count ?? 0)
            );
        }

        Log::info("Paystack subscription.disable: school {$school->id}");
    }

    private function resolveSchoolFromPaystackPayload(array $data): ?School
    {
        $schoolId = (int) data_get($data, 'metadata.school_id', 0);
        if ($schoolId > 0) {
            return School::find($schoolId);
        }

        $customerCode = (string) data_get($data, 'customer.customer_code', '');
        if ($customerCode !== '') {
            $sub = SchoolSubscription::where('paystack_customer_code', $customerCode)->first();
            if ($sub) {
                return School::find($sub->school_id);
            }
        }

        return null;
    }

    private function paymentFailureThreshold(): int
    {
        return max(1, (int) config('paystack.payment_failure_threshold', 3));
    }

    private function paymentFailureGraceDays(): int
    {
        return max(1, (int) config('paystack.payment_failure_grace_days', 7));
    }

    /**
     * School admin billing self-service page.
     */
    public function status()
    {
        $school       = app('currentSchool')->loadMissing('billingPlan');
        $sub          = $school->subscription;
        $studentCount = $school->users()->where('user_type', 'student')->count();
        $billing      = $this->buildBillingSummary($school, $studentCount);

        return view('billing.status', array_merge(
            compact('school', 'studentCount', 'sub'),
            $billing,
            [
                'monthlyRate' => $billing['monthlyRate'],
                'oneTimeRate' => $billing['oneTimeRate'],
                'freeLimit' => $billing['freeLimit'],
                'planName' => $billing['planName'],
            ]
        ));
    }

    private function buildBillingSummary(School $school, int $studentCount): array
    {
        $monthlyRate = $school->effectiveMonthlyRate();
        $oneTimeRate = $school->effectiveOneTimeAddRate();
        $freeLimit = $school->effectiveFreeStudentLimit();
        $planName = $school->billingPlan ? $school->billingPlan->name : 'Standard';

        $billableCount = max(0, $studentCount - $freeLimit);

        $alreadyPaidOneTime = (int) optional($school->subscription)->billed_students;
        $newlyAddedCount = max(0, $billableCount - $alreadyPaidOneTime);

        $monthlyAmount = $billableCount * $monthlyRate;
        $oneTimeAmount = $newlyAddedCount * $oneTimeRate;
        $totalDue = $monthlyAmount + $oneTimeAmount;

        return [
            'planName' => $planName,
            'freeLimit' => $freeLimit,
            'monthlyRate' => $monthlyRate,
            'oneTimeRate' => $oneTimeRate,
            'billableCount' => $billableCount,
            'alreadyPaidOneTime' => $alreadyPaidOneTime,
            'newlyAddedCount' => $newlyAddedCount,
            'monthlyAmount' => $monthlyAmount,
            'oneTimeAmount' => $oneTimeAmount,
            'totalDue' => $totalDue,
        ];
    }
}
