<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table): void {
            $table->id();
            // Random public handle used in admin URLs. Never a numeric id.
            $table->string('code', 24)->unique();

            // Reports are safety records: they stay when the reporter's account or the
            // reported biodata is deleted (the ids become NULL, reported_code keeps the reference).
            $table->foreignId('reporter_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('biodata_id')->nullable()->constrained('biodatas')->nullOnDelete();
            $table->string('reported_code', 20);

            $table->string('reason', 30);
            // Free text from the reporter. Never written to the activity log.
            $table->string('details', 500)->nullable();
            $table->string('status', 20)->default('open');

            // "reporterId-biodataId" while the report is open, NULL otherwise.
            // The unique index lets the database itself refuse a second open
            // report from the same member about the same biodata.
            $table->string('open_key', 41)->nullable()->unique();

            // Filled by staff in Module 7.4.
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->string('resolution', 500)->nullable();

            $table->timestamps();

            // Admin queue: open reports, oldest first.
            $table->index(['status', 'created_at']);
            // Daily limit and "my reports" lookups.
            $table->index(['reporter_user_id', 'created_at']);
            // "All reports about this biodata".
            $table->index('biodata_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
