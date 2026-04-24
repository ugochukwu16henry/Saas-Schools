<?php
// Load Laravel application
require __DIR__ . '/bootstrap/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== DATABASE HEALTH CHECK ===\n\n";

try {
    // Test connection
    DB::connection()->getPdo();
    echo "✓ Database connection successful\n";
} catch (\Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Check for duplicate codes
echo "\n=== CHECKING FOR DUPLICATE CODES ===\n";
$duplicateCodes = DB::table('users')
    ->select('code', DB::raw('COUNT(*) as count'))
    ->groupBy('code')
    ->having('count', '>', 1)
    ->get();

if ($duplicateCodes->count() > 0) {
    echo "⚠ Found " . $duplicateCodes->count() . " duplicate codes:\n";
    foreach ($duplicateCodes as $row) {
        $userIds = DB::table('users')->where('code', $row->code)->pluck('id')->toArray();
        echo "  - Code: {$row->code} (appears {$row->count} times, IDs: " . implode(', ', $userIds) . ")\n";
    }
} else {
    echo "✓ No duplicate codes found\n";
}

// Check for duplicate emails (excluding nulls)
echo "\n=== CHECKING FOR DUPLICATE EMAILS ===\n";
$duplicateEmails = DB::table('users')
    ->whereNotNull('email')
    ->where('email', '!=', '')
    ->select('email', DB::raw('COUNT(*) as count'))
    ->groupBy('email')
    ->having('count', '>', 1)
    ->get();

if ($duplicateEmails->count() > 0) {
    echo "⚠ Found " . $duplicateEmails->count() . " duplicate emails:\n";
    foreach ($duplicateEmails as $row) {
        $userIds = DB::table('users')->where('email', $row->email)->pluck('id')->toArray();
        echo "  - Email: {$row->email} (appears {$row->count} times, IDs: " . implode(', ', $userIds) . ")\n";
    }
} else {
    echo "✓ No duplicate emails found\n";
}

// Check for duplicate usernames (excluding nulls)
echo "\n=== CHECKING FOR DUPLICATE USERNAMES ===\n";
$duplicateUsernames = DB::table('users')
    ->whereNotNull('username')
    ->where('username', '!=', '')
    ->select('username', DB::raw('COUNT(*) as count'))
    ->groupBy('username')
    ->having('count', '>', 1)
    ->get();

if ($duplicateUsernames->count() > 0) {
    echo "⚠ Found " . $duplicateUsernames->count() . " duplicate usernames:\n";
    foreach ($duplicateUsernames as $row) {
        $userIds = DB::table('users')->where('username', $row->username)->pluck('id')->toArray();
        echo "  - Username: {$row->username} (appears {$row->count} times, IDs: " . implode(', ', $userIds) . ")\n";
    }
} else {
    echo "✓ No duplicate usernames found\n";
}

// Check total user count
echo "\n=== USER STATISTICS ===\n";
$totalUsers = DB::table('users')->count();
$studentCount = DB::table('users')->where('user_type', 'student')->count();
$teacherCount = DB::table('users')->where('user_type', 'teacher')->count();
$parentCount = DB::table('users')->where('user_type', 'parent')->count();
$adminCount = DB::table('users')->where('user_type', 'admin')->count();

echo "Total Users: $totalUsers\n";
echo "  - Students: $studentCount\n";
echo "  - Teachers: $teacherCount\n";
echo "  - Parents: $parentCount\n";
echo "  - Admins: $adminCount\n";

// Check for NULL codes
echo "\n=== CHECKING FOR NULL CODES ===\n";
$nullCodes = DB::table('users')->whereNull('code')->count();
if ($nullCodes > 0) {
    echo "⚠ Found $nullCodes users with NULL code (CRITICAL - violates NOT NULL constraint)\n";
} else {
    echo "✓ No NULL codes found\n";
}

echo "\n=== DATABASE HEALTH CHECK COMPLETE ===\n";
