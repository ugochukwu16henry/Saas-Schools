<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddBillingPlanIdToSchools extends Migration
{
    public function up()
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->unsignedBigInteger('billing_plan_id')->nullable()->after('free_student_limit');
            $table->index('billing_plan_id');
            $table->foreign('billing_plan_id')->references('id')->on('billing_plans')->onDelete('set null');
        });

        $defaultPlanId = DB::table('billing_plans')->where('is_default', 1)->value('id');
        if ($defaultPlanId) {
            DB::table('schools')->whereNull('billing_plan_id')->update(['billing_plan_id' => $defaultPlanId]);
        }
    }

    public function down()
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropForeign(['billing_plan_id']);
            $table->dropIndex(['billing_plan_id']);
            $table->dropColumn('billing_plan_id');
        });
    }
}
