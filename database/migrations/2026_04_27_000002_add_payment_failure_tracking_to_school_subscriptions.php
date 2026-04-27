<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentFailureTrackingToSchoolSubscriptions extends Migration
{
    public function up()
    {
        Schema::table('school_subscriptions', function (Blueprint $table) {
            $table->unsignedInteger('payment_failures_count')->default(0)->after('billed_students');
            $table->timestamp('last_payment_failed_at')->nullable()->after('payment_failures_count');
            $table->string('last_payment_failure_reason')->nullable()->after('last_payment_failed_at');
            $table->string('last_payment_reference')->nullable()->after('last_payment_failure_reason');
            $table->timestamp('grace_period_ends_at')->nullable()->after('last_payment_reference');
        });
    }

    public function down()
    {
        Schema::table('school_subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'payment_failures_count',
                'last_payment_failed_at',
                'last_payment_failure_reason',
                'last_payment_reference',
                'grace_period_ends_at',
            ]);
        });
    }
}
