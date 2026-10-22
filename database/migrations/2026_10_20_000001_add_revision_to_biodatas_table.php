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
            // Raised on every real content change. A reviewer's approve/reject is
            // accepted only if the revision is still the one the reviewer saw.
            $table->unsignedInteger('revision')->default(0)->after('completion_pct');
        });
    }

    public function down(): void
    {
        Schema::table('biodatas', function (Blueprint $table): void {
            $table->dropColumn('revision');
        });
    }
};
