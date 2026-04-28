<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSchoolAuditLogsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('school_audit_logs')) {
            return;
        }

        Schema::create('school_audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('school_id');
            $table->string('actor_type', 32)->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('action', 120);
            $table->json('changes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('school_audit_logs');
    }
}
