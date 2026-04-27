<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlatformWebhookDeliveriesTable extends Migration
{
    public function up()
    {
        Schema::create('platform_webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('endpoint_id')->index();
            $table->string('event_type', 100)->index();
            $table->unsignedBigInteger('platform_notification_id')->nullable()->index();
            $table->string('request_id', 64)->index();
            $table->integer('response_status')->nullable();
            $table->boolean('is_success')->default(false)->index();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('attempt')->default(1);
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->foreign('endpoint_id')->references('id')->on('platform_webhook_endpoints')->onDelete('cascade');
            $table->foreign('platform_notification_id')->references('id')->on('platform_notifications')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('platform_webhook_deliveries');
    }
}
