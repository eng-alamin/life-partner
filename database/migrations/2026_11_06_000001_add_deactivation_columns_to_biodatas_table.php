<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('biodatas', function (Blueprint $table): void {
            $table->timestamp('deactivated_at')->nullable()->after('hidden_by');
            // Optional, chosen by the member from a fixed list. Used for simple statistics only. Never logged.
            $table->string('deactivation_reason', 20)->nullable()->after('deactivated_at');
        });
    }

    public function down(): void
    {
        Schema::table('biodatas', function (Blueprint $table): void {
            $table->dropColumn(['deactivated_at', 'deactivation_reason']);
        });
    }
};
