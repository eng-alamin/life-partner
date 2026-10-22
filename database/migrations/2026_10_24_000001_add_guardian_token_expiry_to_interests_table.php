<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interests', function (Blueprint $table): void {
            // The guardian link is valid until this time (7 days by default).
            $table->timestamp('guardian_token_expires_at')->nullable()->after('guardian_token_hash');
        });
    }

    public function down(): void
    {
        Schema::table('interests', function (Blueprint $table): void {
            $table->dropColumn('guardian_token_expires_at');
        });
    }
};
