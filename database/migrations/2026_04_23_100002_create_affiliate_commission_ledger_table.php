<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAffiliateCommissionLedgerTable extends Migration
{
    public function up()
    {
        Schema::create('affiliate_commission_ledger', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('affiliate_id');
            $table->unsignedBigInteger('school_id');
            $table->string('paystack_reference', 100)->unique();
            $table->string('event_type', 50)->default('charge_success');
            $table->unsignedInteger('billable_students_snapshot')->default(0);
            $table->unsignedInteger('newly_added_students_snapshot')->default(0);
            $table->unsignedInteger('one_time_commission_ngn')->default(0);
            $table->unsignedInteger('monthly_commission_ngn')->default(0);
            $table->unsignedInteger('total_commission_ngn')->default(0);
            $table->timestamps();

            $table->foreign('affiliate_id')->references('id')->on('affiliates')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->index(['affiliate_id', 'created_at']);
            $table->index(['school_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('affiliate_commission_ledger');
    }
}
