<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class FixUsersCodeUniqueConstraint extends Migration
{
    /**
     * Run the migrations.
     * 
     * Fix: Change the 'code' field unique constraint from global to per-school (school_id, code)
     * Issue: When multi-tenancy was added, the code field still had a global unique constraint,
     * which prevented adding multiple users across different schools with potentially colliding codes.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop the global unique constraint on 'code'
            $table->dropUnique('users_code_unique');
        });

        // Create a composite unique constraint on (school_id, code)
        // This allows the same code to exist in different schools, but not within the same school
        DB::statement('ALTER TABLE users ADD CONSTRAINT users_school_id_code_unique UNIQUE (school_id, code)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop the composite unique constraint
            DB::statement('ALTER TABLE users DROP INDEX users_school_id_code_unique');
        });

        // Restore the global unique constraint on 'code'
        DB::statement('ALTER TABLE users ADD UNIQUE KEY users_code_unique (code)');
    }
}
