<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddConsecutiveFailuresToPlatformWebhookEndpointsTable extends Migration
{
    public function up()
    {
        Schema::table('platform_webhook_endpoints', function (Blueprint $table) {
            if (! Schema::hasColumn('platform_webhook_endpoints', 'consecutive_failures')) {
                $table->unsignedInteger('consecutive_failures')->default(0)->after('last_failure_at');
            }
        });
    }

    public function down()
    {
        Schema::table('platform_webhook_endpoints', function (Blueprint $table) {
            if (Schema::hasColumn('platform_webhook_endpoints', 'consecutive_failures')) {
                $table->dropColumn('consecutive_failures');
            }
        });
    }
}
