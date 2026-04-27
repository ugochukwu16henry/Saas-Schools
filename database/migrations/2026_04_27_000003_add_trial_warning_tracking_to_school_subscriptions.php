<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTrialWarningTrackingToSchoolSubscriptions extends Migration
{
    public function up()
    {
        Schema::table('school_subscriptions', function (Blueprint $table) {
            $table->timestamp('trial_warning_7d_sent_at')->nullable()->after('grace_period_ends_at');
            $table->timestamp('trial_warning_1d_sent_at')->nullable()->after('trial_warning_7d_sent_at');
        });
    }

    public function down()
    {
        Schema::table('school_subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'trial_warning_7d_sent_at',
                'trial_warning_1d_sent_at',
            ]);
        });
    }
}
