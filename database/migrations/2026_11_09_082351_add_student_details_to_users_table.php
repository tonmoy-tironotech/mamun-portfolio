<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The details are nullable because accounts that already exist don't have them;
     * sign up requires them for new students.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('student_id', 50)->nullable()->unique()->after('email');
            $table->string('education_level', 100)->nullable()->after('student_id');
            $table->string('mobile', 30)->nullable()->after('education_level');
            $table->string('member_status', 20)->default('current')->after('is_admin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['student_id']);
            $table->dropColumn(['student_id', 'education_level', 'mobile', 'member_status']);
        });
    }
};
