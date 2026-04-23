<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAffiliatesTable extends Migration
{
    public function up()
    {
        Schema::create('affiliates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 30);
            $table->string('country', 10)->nullable();
            $table->text('bio')->nullable();
            $table->string('password')->nullable();
            $table->enum('status', ['pending', 'approved', 'suspended'])->default('pending');
            $table->string('code', 32)->nullable()->unique();
            $table->string('photo_path')->nullable();
            $table->string('bank_name')->nullable();
            $table->text('account_number')->nullable();
            $table->string('account_name')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->index('status');
            $table->foreign('approved_by')->references('id')->on('platform_admins')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('affiliates');
    }
}
