<?php

namespace App\Services;

use App\Models\Affiliate;

class AffiliateReferralService
{
    /**
     * Resolve approved affiliate primary key from referral code (case-insensitive).
     */
    public function resolveAffiliateId(?string $code): ?int
    {
        if ($code === null || trim($code) === '') {
            return null;
        }

        $code = strtoupper(trim($code));

        return Affiliate::query()
            ->where('status', 'approved')
            ->whereRaw('UPPER(code) = ?', [$code])
            ->value('id');
    }
}
