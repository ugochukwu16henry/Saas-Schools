<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlatformNotificationsTable extends Migration
{
    public function up()
    {
        Schema::create('platform_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type', 100)->index();
            $table->string('title');
            $table->text('message');
            $table->unsignedBigInteger('school_id')->nullable()->index();
            $table->json('payload')->nullable();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('platform_notifications');
    }
}
