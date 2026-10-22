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
            // The "identity verified" badge. Set only when staff approve a request, and cleared when
            // the verified facts change. Kept on the biodata so search cards need no extra query.
            $table->timestamp('identity_verified_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('biodatas', function (Blueprint $table): void {
            $table->dropColumn('identity_verified_at');
        });
    }
};
