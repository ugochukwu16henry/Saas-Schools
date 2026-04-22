<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSchoolIdToTenantTables extends Migration
{
    /**
     * Tables that need a school_id column for multi-tenancy.
     * Reference-only tables (blood_groups, states, lgas, nationalities,
     * class_types, user_types) are shared across all schools and excluded.
     */
    private array $tables = [
        'users',
        'my_classes',
        'sections',
        'subjects',
        'exams',
        'marks',
        'grades',
        'skills',
        'exam_records',
        'student_records',
        'staff_records',
        'payments',
        'payment_records',
        'receipts',
        'time_tables',
        'pins',
        'books',
        'book_requests',
        'settings',
        'dorms',
        'promotions',
    ];

    public function up()
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->unsignedBigInteger('school_id')->nullable()->after('id');
                $t->index('school_id');
            });
        }
    }

    public function down()
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->dropIndex([$table === 'users' ? 'users_school_id_index' : "{$table}_school_id_index"]);
                $t->dropColumn('school_id');
            });
        }
    }
}
