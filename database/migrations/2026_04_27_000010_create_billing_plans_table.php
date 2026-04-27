<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateBillingPlansTable extends Migration
{
    public function up()
    {
        Schema::create('billing_plans', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 120)->unique();
            $table->unsignedInteger('monthly_rate_per_student')->default(100);
            $table->unsignedInteger('one_time_add_rate')->default(500);
            $table->unsignedInteger('default_free_student_limit')->default(50);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['is_active', 'is_default']);
        });

        DB::table('billing_plans')->insert([
            'name' => 'Standard',
            'monthly_rate_per_student' => 100,
            'one_time_add_rate' => 500,
            'default_free_student_limit' => 50,
            'is_active' => 1,
            'is_default' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('billing_plans');
    }
}
