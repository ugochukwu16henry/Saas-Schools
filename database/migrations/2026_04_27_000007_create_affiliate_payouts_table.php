<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAffiliatePayoutsTable extends Migration
{
    public function up()
    {
        Schema::create('affiliate_payouts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('affiliate_id')->index();
            $table->unsignedBigInteger('approved_by')->nullable()->index();
            $table->unsignedBigInteger('paid_by')->nullable()->index();
            $table->unsignedBigInteger('amount_ngn');
            $table->string('status', 30)->default('pending')->index();
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('affiliate_id')->references('id')->on('affiliates')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('platform_admins')->onDelete('set null');
            $table->foreign('paid_by')->references('id')->on('platform_admins')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('affiliate_payouts');
    }
}
