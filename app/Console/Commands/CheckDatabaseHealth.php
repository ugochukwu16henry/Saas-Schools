<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckDatabaseHealth extends Command
{
    protected $signature = 'db:health-check';
    protected $description = 'Check database health and look for duplicate codes/emails/usernames';

    public function handle()
    {
        $this->info('=== DATABASE HEALTH CHECK ===');
        $this->newLine();

        // Test connection
        try {
            DB::connection()->getPdo();
            $this->info('✓ Database connection successful');
        } catch (\Exception $e) {
            $this->error('✗ Database connection failed: ' . $e->getMessage());
            return 1;
        }

        $this->newLine();
        $this->info('=== CHECKING FOR DUPLICATE CODES ===');
        $duplicateCodes = DB::table('users')
            ->select('code', DB::raw('COUNT(*) as count'))
            ->groupBy('code')
            ->having('count', '>', 1)
            ->get();

        if ($duplicateCodes->count() > 0) {
            $this->warn('⚠ Found ' . $duplicateCodes->count() . ' duplicate codes:');
            foreach ($duplicateCodes as $row) {
                $userIds = DB::table('users')->where('code', $row->code)->pluck('id')->toArray();
                $this->line("  - Code: {$row->code} (appears {$row->count} times, IDs: " . implode(', ', $userIds) . ')');
            }
        } else {
            $this->info('✓ No duplicate codes found');
        }

        $this->newLine();
        $this->info('=== CHECKING FOR DUPLICATE EMAILS ===');
        $duplicateEmails = DB::table('users')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->select('email', DB::raw('COUNT(*) as count'))
            ->groupBy('email')
            ->having('count', '>', 1)
            ->get();

        if ($duplicateEmails->count() > 0) {
            $this->warn('⚠ Found ' . $duplicateEmails->count() . ' duplicate emails:');
            foreach ($duplicateEmails as $row) {
                $userIds = DB::table('users')->where('email', $row->email)->pluck('id')->toArray();
                $this->line("  - Email: {$row->email} (appears {$row->count} times, IDs: " . implode(', ', $userIds) . ')');
            }
        } else {
            $this->info('✓ No duplicate emails found');
        }

        $this->newLine();
        $this->info('=== CHECKING FOR DUPLICATE USERNAMES ===');
        $duplicateUsernames = DB::table('users')
            ->whereNotNull('username')
            ->where('username', '!=', '')
            ->select('username', DB::raw('COUNT(*) as count'))
            ->groupBy('username')
            ->having('count', '>', 1)
            ->get();

        if ($duplicateUsernames->count() > 0) {
            $this->warn('⚠ Found ' . $duplicateUsernames->count() . ' duplicate usernames:');
            foreach ($duplicateUsernames as $row) {
                $userIds = DB::table('users')->where('username', $row->username)->pluck('id')->toArray();
                $this->line("  - Username: {$row->username} (appears {$row->count} times, IDs: " . implode(', ', $userIds) . ')');
            }
        } else {
            $this->info('✓ No duplicate usernames found');
        }

        $this->newLine();
        $this->info('=== USER STATISTICS ===');
        $totalUsers = DB::table('users')->count();
        $studentCount = DB::table('users')->where('user_type', 'student')->count();
        $teacherCount = DB::table('users')->where('user_type', 'teacher')->count();
        $parentCount = DB::table('users')->where('user_type', 'parent')->count();
        $adminCount = DB::table('users')->where('user_type', 'admin')->count();
        $affiliateCount = DB::table('users')->where('user_type', 'affiliate')->count();

        $this->line("Total Users: $totalUsers");
        $this->line("  - Students: $studentCount");
        $this->line("  - Teachers: $teacherCount");
        $this->line("  - Parents: $parentCount");
        $this->line("  - Admins: $adminCount");
        $this->line("  - Affiliates: $affiliateCount");

        // Check for NULL codes
        $this->newLine();
        $this->info('=== CHECKING FOR NULL CODES ===');
        $nullCodes = DB::table('users')->whereNull('code')->count();
        if ($nullCodes > 0) {
            $this->error("⚠ Found $nullCodes users with NULL code (CRITICAL - violates NOT NULL constraint)");
        } else {
            $this->info('✓ No NULL codes found');
        }

        $this->newLine();
        $this->info('=== DATABASE HEALTH CHECK COMPLETE ===');

        return 0;
    }
}
