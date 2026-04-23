<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAffiliateIdToSchoolsTable extends Migration
{
    public function up()
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->unsignedBigInteger('affiliate_id')->nullable()->after('paystack_customer_code');
            $table->timestamp('affiliate_attributed_at')->nullable()->after('affiliate_id');
            $table->foreign('affiliate_id')->references('id')->on('affiliates')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropForeign(['affiliate_id']);
            $table->dropColumn(['affiliate_id', 'affiliate_attributed_at']);
        });
    }
}
