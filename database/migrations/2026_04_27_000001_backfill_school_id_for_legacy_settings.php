<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillSchoolIdForLegacySettings extends Migration
{
    /**
     * Copy legacy global settings rows (school_id = null) into per-school rows,
     * only for setting types each school does not already have.
     */
    public function up()
    {
        if (!Schema::hasTable('settings') || !Schema::hasTable('schools')) {
            return;
        }

        if (!Schema::hasColumn('settings', 'school_id')) {
            return;
        }

        $legacyRows = DB::table('settings')
            ->whereNull('school_id')
            ->orderBy('id')
            ->get(['type', 'description', 'created_at', 'updated_at']);

        if ($legacyRows->isEmpty()) {
            return;
        }

        $schoolIds = DB::table('schools')->pluck('id');
        if ($schoolIds->isEmpty()) {
            return;
        }

        foreach ($schoolIds as $schoolId) {
            $existingTypes = DB::table('settings')
                ->where('school_id', $schoolId)
                ->pluck('type')
                ->flip();

            $toInsert = [];
            foreach ($legacyRows as $row) {
                if ($existingTypes->has($row->type)) {
                    continue;
                }

                $toInsert[] = [
                    'school_id' => $schoolId,
                    'type' => $row->type,
                    'description' => $row->description,
                    'created_at' => $row->created_at ?: now(),
                    'updated_at' => $row->updated_at ?: now(),
                ];

                // Avoid duplicate inserts for duplicate legacy types in same run.
                $existingTypes->put($row->type, true);
            }

            if (!empty($toInsert)) {
                foreach (array_chunk($toInsert, 500) as $chunk) {
                    DB::table('settings')->insert($chunk);
                }
            }
        }
    }

    /**
     * Intentionally left as no-op to avoid deleting valid tenant settings.
     */
    public function down()
    {
        // No-op.
    }
}
