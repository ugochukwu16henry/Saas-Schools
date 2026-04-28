<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProcessedWebhookEventsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('processed_webhook_events')) {
            return;
        }

        Schema::create('processed_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 40)->index();
            $table->string('event_key', 190)->unique();
            $table->string('event_type', 120)->nullable()->index();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('processed_webhook_events');
    }
}
