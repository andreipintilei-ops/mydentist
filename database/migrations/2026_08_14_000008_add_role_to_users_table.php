<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // admin | moderator | clinic_owner | user. Hand-rolled RBAC (no
            // package) gated by ClinicPolicy — matches the project's existing habit.
            $table->enum('role', ['admin', 'moderator', 'clinic_owner', 'user'])
                ->default('user')
                ->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
