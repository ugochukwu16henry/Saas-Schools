<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOnboardingCompletedToSchools extends Migration
{
    public function up()
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->timestamp('onboarding_completed_at')->nullable()->after('affiliate_attributed_at');
        });
    }

    public function down()
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn('onboarding_completed_at');
        });
    }
}
