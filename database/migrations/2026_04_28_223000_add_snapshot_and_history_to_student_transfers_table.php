<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('student_transfers', function (Blueprint $table): void {
            $table->json('transfer_snapshot')->nullable()->after('transferred_at');
            $table->json('status_history')->nullable()->after('transfer_snapshot');
        });

        DB::table('student_transfers')
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $history = [
                        [
                            'event' => 'requested',
                            'status' => 'pending',
                            'actor_id' => $row->requested_by,
                            'at' => (string) $row->created_at,
                        ],
                    ];

                    if ($row->status === 'accepted') {
                        $history[] = [
                            'event' => 'accepted',
                            'status' => 'accepted',
                            'actor_id' => $row->accepted_by,
                            'at' => (string) ($row->transferred_at ?: $row->updated_at),
                        ];
                    } elseif ($row->status === 'rejected') {
                        $history[] = [
                            'event' => 'rejected',
                            'status' => 'rejected',
                            'actor_id' => $row->accepted_by,
                            'reason' => $row->rejected_reason,
                            'at' => (string) $row->updated_at,
                        ];
                    } elseif ($row->status === 'cancelled') {
                        $history[] = [
                            'event' => 'cancelled',
                            'status' => 'cancelled',
                            'actor_id' => $row->requested_by,
                            'at' => (string) $row->updated_at,
                        ];
                    }

                    DB::table('student_transfers')
                        ->where('id', $row->id)
                        ->update([
                            'status_history' => json_encode($history),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('student_transfers', function (Blueprint $table): void {
            $table->dropColumn(['transfer_snapshot', 'status_history']);
        });
    }
};
