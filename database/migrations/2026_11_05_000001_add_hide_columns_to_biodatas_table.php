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
            $table->timestamp('hidden_at')->nullable()->after('reviewed_at');
            // Who hid it: 'owner' or 'staff'. NULL on rows hidden before this column existed: treated as staff.
            $table->string('hidden_source', 10)->nullable()->after('hidden_at');
            // The status to go back to: 'approved' or 'pending'.
            $table->string('hidden_from', 10)->nullable()->after('hidden_source');
            // The revision at the moment of hiding. If it is unchanged on restore, no re-review is needed.
            $table->unsignedInteger('hidden_revision')->nullable()->after('hidden_from');
            // A plain id on purpose (the member or the staff member who hid it).
            $table->unsignedBigInteger('hidden_by')->nullable()->after('hidden_revision');

            $table->index(['status', 'hidden_source'], 'biodatas_status_hidden_source_index');
        });
    }

    public function down(): void
    {
        Schema::table('biodatas', function (Blueprint $table): void {
            $table->dropIndex('biodatas_status_hidden_source_index');
            $table->dropColumn(['hidden_at', 'hidden_source', 'hidden_from', 'hidden_revision', 'hidden_by']);
        });
    }
};
