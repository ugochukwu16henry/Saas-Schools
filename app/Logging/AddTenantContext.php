<?php

namespace App\Logging;

use Illuminate\Support\Facades\Auth;

class AddTenantContext
{
    /**
     * Add tenant/user context to each log record.
     */
    public function __invoke($logger): void
    {
        $logger->pushProcessor(function (array $record): array {
            $schoolId = app()->bound('currentSchool') ? optional(app('currentSchool'))->id : null;
            $requestId = null;

            try {
                if (app()->bound('request')) {
                    $requestId = request()->headers->get('X-Request-Id');
                }
            } catch (\Throwable $e) {
                $requestId = null;
            }

            $record['extra']['school_id'] = $schoolId;
            $record['extra']['user_id'] = Auth::id();
            $record['extra']['request_id'] = $requestId;

            return $record;
        });
    }
}
