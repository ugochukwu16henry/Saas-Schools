<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddAffiliateCommissionRatesToBillingPlansTable extends Migration
{
    public function up()
    {
        Schema::table('billing_plans', function (Blueprint $table) {
            $table->unsignedInteger('affiliate_one_time_commission_per_student')->default(200)->after('one_time_add_rate');
            $table->unsignedInteger('affiliate_monthly_commission_per_student')->default(100)->after('affiliate_one_time_commission_per_student');
        });

        DB::table('billing_plans')->update([
            'monthly_rate_per_student' => 500,
            'one_time_add_rate' => 1000,
            'affiliate_one_time_commission_per_student' => 200,
            'affiliate_monthly_commission_per_student' => 100,
            'default_free_student_limit' => 50,
            'updated_at' => now(),
        ]);
    }

    public function down()
    {
        Schema::table('billing_plans', function (Blueprint $table) {
            $table->dropColumn([
                'affiliate_one_time_commission_per_student',
                'affiliate_monthly_commission_per_student',
            ]);
        });
    }
}