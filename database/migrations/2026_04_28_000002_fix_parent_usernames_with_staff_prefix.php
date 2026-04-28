<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('users')
            ->select(['id', 'username'])
            ->where('user_type', 'parent')
            ->where('username', 'like', '%/STAFF/%')
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $current = (string) $row->username;
                    $base = str_replace('/STAFF/', '/PARENT/', $current);
                    $candidate = $base;

                    if ($this->usernameExists($candidate, (int) $row->id)) {
                        $candidate = $base . '-P' . (int) $row->id;
                    }

                    DB::table('users')
                        ->where('id', (int) $row->id)
                        ->update(['username' => $candidate]);
                }
            }, 'id');
    }

    public function down(): void
    {
        // Irreversible safely: usernames may have been edited after the fix.
    }

    private function usernameExists(string $username, int $exceptId): bool
    {
        return DB::table('users')
            ->where('username', $username)
            ->where('id', '!=', $exceptId)
            ->exists();
    }
};
