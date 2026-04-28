<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSchoolSubscriptionsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('school_subscriptions')) {
            return;
        }

        Schema::create('school_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('paystack_subscription_code')->nullable();
            $table->string('paystack_customer_code')->nullable();
            $table->enum('status', ['active', 'cancelled', 'expired', 'trialling'])->default('trialling');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('next_payment_date')->nullable();
            $table->unsignedInteger('billed_students')->default(0);
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('school_subscriptions');
    }
}
