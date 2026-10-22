<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            // When the "ends soon" message and the "has ended" message were sent.
            // A filled column means "already sent", so a message is never sent twice.
            $table->timestamp('reminded_at')->nullable()->after('cancelled_at');
            $table->timestamp('expired_notice_at')->nullable()->after('reminded_at');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropColumn(['reminded_at', 'expired_notice_at']);
        });
    }
};
