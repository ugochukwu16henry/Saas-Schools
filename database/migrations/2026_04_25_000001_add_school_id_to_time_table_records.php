<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSchoolIdToTimeTableRecords extends Migration
{
    public function up()
    {
        Schema::table('time_table_records', function (Blueprint $table) {
            $table->unsignedBigInteger('school_id')->nullable()->after('id');
            $table->index('school_id');
        });

        // The original table had a global unique on 'name'. With multi-tenancy,
        // uniqueness must be scoped per school, not globally.
        Schema::table('time_table_records', function (Blueprint $table) {
            $table->dropUnique('time_table_records_name_unique');
            $table->unique(['school_id', 'name']);
        });
    }

    public function down()
    {
        Schema::table('time_table_records', function (Blueprint $table) {
            $table->dropUnique(['school_id', 'name']);
            $table->dropIndex('time_table_records_school_id_index');
            $table->dropColumn('school_id');
        });

        Schema::table('time_table_records', function (Blueprint $table) {
            $table->unique('name');
        });
    }
}
