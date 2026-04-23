<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAiAuditLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ai_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ai_request_id');
            $table->string('event', 80)->index();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->foreign('ai_request_id')->references('id')->on('ai_requests')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ai_audit_logs');
    }
}
