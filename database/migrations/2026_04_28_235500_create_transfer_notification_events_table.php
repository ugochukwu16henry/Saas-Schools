<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('transfer_notification_events')) {
            return;
        }

        Schema::create('transfer_notification_events', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('transfer_id')->nullable();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->unsignedBigInteger('notifiable_id')->nullable();
            $table->string('notifiable_email')->nullable();
            $table->string('notification_class');
            $table->string('channel', 50)->default('mail');
            $table->string('status', 20);
            $table->text('error')->nullable();
            $table->json('payload')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status']);
            $table->index(['transfer_id', 'status']);
            $table->index(['notification_class', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_notification_events');
    }
};
