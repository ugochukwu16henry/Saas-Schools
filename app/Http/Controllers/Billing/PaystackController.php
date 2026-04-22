<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackController extends Controller
{
    private string $secretKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->secretKey = config('paystack.secret_key', '');
        $this->baseUrl   = config('paystack.payment_url', 'https://api.paystack.co');
    }

    /**
     * Show the billing required prompt page.
     */
    public function prompt()
    {
        $school = app('currentSchool');
        $studentCount = $school->users()->where('user_type', 'student')->count();
        $billableCount = max(0, $studentCount - $school->free_student_limit);
        $monthlyAmount = $billableCount * 100; // ₦100 per billable student

        return view('billing.prompt', compact('school', 'studentCount', 'billableCount', 'monthlyAmount'));
    }

    /**
     * Initialize a Paystack transaction and redirect to payment page.
     */
    public function initialize(Request $request)
    {
        $school = app('currentSchool');
        $user   = auth()->user();

        $studentCount  = $school->users()->where('user_type', 'student')->count();
        $billableCount = max(0, $studentCount - $school->free_student_limit);
        $amountKobo    = $billableCount * 100 * 100; // ₦100/student in kobo

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
                    'billed_students' => $billableCount,
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
            return redirect()->route('billing.prompt')->withErrors(['billing' => 'Payment was not successful.']);
        }

        $schoolId       = data_get($data, 'metadata.school_id');
        $billedStudents = (int) data_get($data, 'metadata.billed_students', 0);

        $school = School::find($schoolId);
        if (!$school) {
            return redirect()->route('login');
        }

        // Update or create subscription record
        SchoolSubscription::updateOrCreate(
            ['school_id' => $school->id],
            [
                'status'            => 'active',
                'billed_students'   => $billedStudents,
                'next_payment_date' => now()->addMonth(),
                'paystack_customer_code' => data_get($data, 'customer.customer_code', ''),
            ]
        );

        // Ensure school status is active
        $school->update(['status' => 'active']);

        return redirect()->route('dashboard')->with('status', 'Payment successful! Your subscription is now active.');
    }

    /**
     * Handle Paystack webhook events.
     * Route must be CSRF-exempt (add to VerifyCsrfToken $except).
     */
    public function webhook(Request $request)
    {
        $signature = $request->header('X-Paystack-Signature');
        $payload   = $request->getContent();

        if (!hash_equals(hash_hmac('sha512', $payload, $this->secretKey), $signature)) {
            abort(401, 'Invalid webhook signature');
        }

        $event = $request->input('event');
        $data  = $request->input('data');

        match ($event) {
            'charge.success'           => $this->handleChargeSuccess($data),
            'subscription.disable'     => $this->handleSubscriptionDisabled($data),
            default                    => null,
        };

        return response()->json(['status' => 'ok']);
    }

    private function handleChargeSuccess(array $data): void
    {
        $schoolId = data_get($data, 'metadata.school_id');
        if (!$schoolId) return;

        $school = School::find($schoolId);
        if (!$school) return;

        $billedStudents = (int) data_get($data, 'metadata.billed_students', 0);

        SchoolSubscription::updateOrCreate(
            ['school_id' => $school->id],
            [
                'status'            => 'active',
                'billed_students'   => $billedStudents,
                'next_payment_date' => now()->addMonth(),
                'paystack_customer_code' => data_get($data, 'customer.customer_code', ''),
            ]
        );

        $school->update(['status' => 'active']);

        Log::info("Paystack charge.success: school {$school->id}");
    }

    private function handleSubscriptionDisabled(array $data): void
    {
        $schoolId = data_get($data, 'metadata.school_id');
        if (!$schoolId) return;

        $sub = SchoolSubscription::where('school_id', $schoolId)->first();
        if ($sub) {
            $sub->update(['status' => 'cancelled']);
        }

        Log::info("Paystack subscription.disable: school {$schoolId}");
    }
}
