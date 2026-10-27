<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 40)->default('customer')->index()->after('password');
            $table->string('phone', 30)->nullable()->after('role');
            $table->text('mfa_secret')->nullable()->after('phone');
            $table->boolean('mfa_enabled')->default(false)->after('mfa_secret');
            $table->boolean('is_active')->default(true)->index()->after('mfa_enabled');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['role', 'phone', 'mfa_secret', 'mfa_enabled', 'is_active', 'last_login_at']);
        });
    }
};
