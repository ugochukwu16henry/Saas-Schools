<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class CreatePlatformAdminsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('platform_admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        // Create an initial owner account for local setup; change this in production.
        $email = env('PLATFORM_ADMIN_EMAIL', 'owner@saas-schools.test');
        $password = env('PLATFORM_ADMIN_PASSWORD', 'ChangeMe123!');

        \Illuminate\Support\Facades\DB::table('platform_admins')->insert([
            'name' => 'Platform Owner',
            'email' => $email,
            'password' => Hash::make($password),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('platform_admins');
    }
}
