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
            // When the member asked to delete the account, and the day the data may be purged (14 days later).
            $table->timestamp('deletion_requested_at')->nullable()->after('suspension_reason');
            $table->timestamp('deletion_scheduled_for')->nullable()->after('deletion_requested_at');

            // The daily purge job looks for accounts whose day has come.
            $table->index('deletion_scheduled_for', 'users_deletion_scheduled_for_index');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_deletion_scheduled_for_index');
            $table->dropColumn(['deletion_requested_at', 'deletion_scheduled_for']);
        });
    }
};
