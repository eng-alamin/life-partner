<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_ledger', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // Signed: positive for a grant, negative for a spend or a reversal.
            $table->integer('delta');
            // purchase, membership_grant, free_grant, admin_adjust, unlock, refund_reversal.
            $table->string('reason', 30);

            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->foreignId('interest_id')->nullable()->constrained('interests')->nullOnDelete();

            // Makes a grant happen once: "payment-12-purchase" or "free-user-5".
            // NULL for rows that need no such guard. Rows with NULL are not compared.
            $table->string('grant_key', 60)->nullable()->unique();
            // Credits that came with a membership period expire with it.
            $table->timestamp('expires_at')->nullable();
            // Short fixed code only, never free text.
            $table->string('note_code', 30)->nullable();

            // Append-only: no updated_at.
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_ledger');
    }
};
