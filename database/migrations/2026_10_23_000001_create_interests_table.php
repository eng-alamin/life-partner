<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interests', function (Blueprint $table): void {
            $table->id();
            // Random public handle used in URLs. Never a numeric id.
            $table->string('code', 24)->unique();
            $table->foreignId('sender_biodata_id')->constrained('biodatas')->cascadeOnDelete();
            $table->foreignId('receiver_biodata_id')->constrained('biodatas')->cascadeOnDelete();
            $table->string('status', 20)->default('pending');
            // Short optional note from the sender (checked for contact details in code).
            $table->string('message', 300)->nullable();

            // Guardian approval of the receiver's side: not_required, pending, approved, declined.
            $table->string('guardian_status', 20)->default('not_required');
            // Only a SHA-256 hash of the one-time guardian link token is stored.
            $table->string('guardian_token_hash', 64)->nullable()->unique();
            $table->timestamp('guardian_decided_at')->nullable();

            $table->timestamp('responded_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            // "senderId-receiverId" while the request is pending, NULL otherwise.
            // The unique index lets the database itself refuse a second open
            // request for the same pair, even if two requests race.
            $table->string('pending_pair', 41)->nullable()->unique();

            $table->timestamps();

            $table->index(['receiver_biodata_id', 'status']);
            $table->index(['sender_biodata_id', 'created_at']);
            $table->index(['sender_biodata_id', 'receiver_biodata_id']);
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interests');
    }
};
