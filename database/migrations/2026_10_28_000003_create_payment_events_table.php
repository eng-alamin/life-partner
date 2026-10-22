<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->string('gateway', 20);
            // init, submitted, review, return_success, return_fail, return_cancel, ipn, validation.
            $table->string('event_type', 30);
            // Hash that recognises a replayed gateway message. Optional for manual events.
            $table->string('idempotency_key', 64)->nullable()->unique();
            // Already cleaned of secrets and card-like data by the caller.
            $table->json('payload')->nullable();
            $table->boolean('signature_valid')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['payment_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_events');
    }
};
