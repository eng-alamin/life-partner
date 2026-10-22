<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // One paid membership payment creates at most one subscription.
            $table->foreignId('payment_id')->nullable()->unique()->constrained('payments')->nullOnDelete();

            // Snapshot of the plan at purchase.
            $table->string('plan_code', 40);
            $table->string('plan_name', 120);
            $table->unsignedSmallInteger('daily_interest_limit')->nullable();

            // pending, active, expired, cancelled. Separate from the payment status.
            $table->string('status', 20)->default('pending');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            // "Does this member have an active membership now?"
            $table->index(['user_id', 'status', 'ends_at']);
            // Hourly expiry job.
            $table->index(['status', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
