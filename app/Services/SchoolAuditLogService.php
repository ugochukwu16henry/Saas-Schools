<?php

namespace App\Services;

use App\Models\School;
use App\Models\SchoolAuditLog;
use Illuminate\Support\Facades\Auth;

class SchoolAuditLogService
{
    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  array<string, mixed>  $meta
     */
    public function logDiff(School $school, string $action, array $before, array $after, array $meta = []): ?SchoolAuditLog
    {
        $changes = $this->buildChanges($before, $after);
        if ($changes === []) {
            return null;
        }

        [$actorType, $actorId] = $this->resolveActor();

        return SchoolAuditLog::create([
            'school_id' => $school->id,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'action' => $action,
            'changes' => $changes,
            'meta' => array_merge($meta, $this->requestMeta()),
        ]);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function logEvent(School $school, string $action, array $meta = []): SchoolAuditLog
    {
        [$actorType, $actorId] = $this->resolveActor();

        return SchoolAuditLog::create([
            'school_id' => $school->id,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'action' => $action,
            'changes' => null,
            'meta' => array_merge($meta, $this->requestMeta()),
        ]);
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array<string, array{from: mixed, to: mixed}>
     */
    private function buildChanges(array $before, array $after): array
    {
        $keys = array_values(array_unique(array_merge(array_keys($before), array_keys($after))));
        $changes = [];

        foreach ($keys as $key) {
            $old = $before[$key] ?? null;
            $new = $after[$key] ?? null;

            if ((string) $old === (string) $new) {
                continue;
            }

            $changes[$key] = [
                'from' => $old,
                'to' => $new,
            ];
        }

        return $changes;
    }

    /**
     * @return array{0: string, 1: int|null}
     */
    private function resolveActor(): array
    {
        if (Auth::guard('platform')->check()) {
            return ['platform_admin', Auth::guard('platform')->id()];
        }

        if (Auth::check()) {
            return ['user', Auth::id()];
        }

        return ['system', null];
    }

    /**
     * @return array<string, mixed>
     */
    private function requestMeta(): array
    {
        if (! app()->bound('request')) {
            return [];
        }

        $request = request();

        return [
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ];
    }
}
