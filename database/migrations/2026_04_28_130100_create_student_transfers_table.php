<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_transfers', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedInteger('student_id');
            $table->unsignedBigInteger('from_school_id');
            $table->unsignedBigInteger('to_school_id');
            $table->unsignedInteger('requested_by');
            $table->unsignedInteger('accepted_by')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected', 'cancelled'])->default('pending');
            $table->unsignedInteger('from_class_id')->nullable();
            $table->unsignedInteger('from_section_id')->nullable();
            $table->string('from_session')->nullable();
            $table->text('transfer_note')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->dateTime('transferred_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'status']);
            $table->index(['from_school_id', 'status']);
            $table->index(['to_school_id', 'status']);

            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('from_school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->foreign('to_school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('accepted_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_transfers');
    }
};
