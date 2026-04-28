<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_qr_tokens', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedInteger('student_id');
            $table->unsignedInteger('school_id');
            $table->string('token', 64)->unique();
            $table->timestamps();

            $table->unique('student_id');
            $table->index('school_id');

            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_qr_tokens');
    }
};
