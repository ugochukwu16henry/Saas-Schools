<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            if (!Schema::hasColumn('schools', 'unique_code')) {
                $table->string('unique_code', 16)->nullable()->after('slug');
            }
        });

        DB::table('schools')
            ->select(['id'])
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $existing = DB::table('schools')->where('id', (int) $row->id)->value('unique_code');
                    if ($existing) {
                        continue;
                    }

                    DB::table('schools')
                        ->where('id', (int) $row->id)
                        ->update(['unique_code' => $this->generateUniqueCode()]);
                }
            }, 'id');

        Schema::table('schools', function (Blueprint $table): void {
            $table->unique('unique_code');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            $table->dropUnique('schools_unique_code_unique');
            $table->dropColumn('unique_code');
        });
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
            $exists = DB::table('schools')->where('unique_code', $code)->exists();
        } while ($exists);

        return $code;
    }
};
