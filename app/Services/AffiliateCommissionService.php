<?php

namespace App\Services;

use App\Models\AffiliateCommissionLedger;
use App\Models\School;
use Illuminate\Support\Facades\Log;

class AffiliateCommissionService
{
    /**
     * Record affiliate commission from a successful Paystack charge (idempotent).
     *
     * @param  array  $data  Paystack transaction "data" object (verify or webhook)
     */
    public function recordFromPaystackCharge(array $data): void
    {
        $reference = (string) (data_get($data, 'reference') ?: data_get($data, 'id'));
        if ($reference === '') {
            return;
        }

        if (AffiliateCommissionLedger::query()->where('paystack_reference', $reference)->exists()) {
            return;
        }

        $schoolId = data_get($data, 'metadata.school_id');
        if (! $schoolId) {
            return;
        }

        $school = School::query()->find($schoolId);
        if (! $school || ! $school->affiliate_id) {
            return;
        }

        $billable = max(0, (int) data_get($data, 'metadata.billable_students', 0));
        $newlyAdded = max(0, (int) data_get($data, 'metadata.newly_added_students', 0));

        $oneTimeRate = (int) config('affiliate.one_time_per_new_billable_student', 60);
        $monthlyRate = (int) config('affiliate.monthly_per_billable_student', 20);

        $oneTimeNgn = $newlyAdded * $oneTimeRate;
        $monthlyNgn = $billable * $monthlyRate;
        $total = $oneTimeNgn + $monthlyNgn;

        if ($total <= 0) {
            return;
        }

        AffiliateCommissionLedger::create([
            'affiliate_id' => $school->affiliate_id,
            'school_id' => $school->id,
            'paystack_reference' => $reference,
            'event_type' => 'charge_success',
            'billable_students_snapshot' => $billable,
            'newly_added_students_snapshot' => $newlyAdded,
            'one_time_commission_ngn' => $oneTimeNgn,
            'monthly_commission_ngn' => $monthlyNgn,
            'total_commission_ngn' => $total,
        ]);

        Log::info('affiliate_commission_recorded', [
            'affiliate_id' => $school->affiliate_id,
            'school_id' => $school->id,
            'reference' => $reference,
            'total_ngn' => $total,
        ]);
    }
}
