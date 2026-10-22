<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('suspended_at')->nullable()->after('last_login_at');
            // The staff member who suspended the account. A plain id on purpose: users are soft deleted, so it stays valid.
            $table->unsignedBigInteger('suspended_by')->nullable()->after('suspended_at');
            // Written by staff, read by staff only. Never logged, never shown to the member.
            $table->string('suspension_reason', 500)->nullable()->after('suspended_by');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['suspended_at', 'suspended_by', 'suspension_reason']);
        });
    }
};
