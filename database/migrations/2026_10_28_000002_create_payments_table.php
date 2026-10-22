<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            // Random public handle used in URLs. Never a numeric id.
            $table->string('code', 24)->unique();

            // Money records must survive a deleted account or plan, so these become NULL.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();

            // Snapshot of the plan at checkout. Later plan edits never change these.
            $table->string('plan_code', 40);
            $table->string('plan_name', 120);
            $table->string('plan_kind', 20);
            $table->unsignedSmallInteger('credits')->default(0);
            $table->unsignedSmallInteger('duration_days')->nullable();
            $table->unsignedSmallInteger('daily_interest_limit')->nullable();

            // Integer poisha, copied from the plan row. Never from a request.
            $table->unsignedInteger('amount_minor');
            $table->char('currency', 3)->default('BDT');

            // manual_bkash, bkash or sslcommerz.
            $table->string('gateway', 20);
            // Our own reference for this payment (sent to a gateway, shown to the member).
            $table->string('tran_id', 40)->unique();
            // The other side's id: the bKash TrxID or the gateway's transaction id.
            $table->string('gateway_ref', 64)->nullable();
            // Where gateway_ref must be unique: manual bKash and the bKash API share "bkash".
            $table->string('ref_scope', 20)->nullable();
            // Manual bKash only: the number the member sent the money from.
            $table->string('payer_number', 20)->nullable();

            // pending, under_review, paid, failed, cancelled, refunded.
            $table->string('status', 20)->default('pending');
            $table->string('failure_reason', 40)->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            // Set once, when credits or a subscription were granted for this payment.
            $table->timestamp('fulfilled_at')->nullable();

            // Staff review (manual payments).
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_note', 255)->nullable();

            $table->timestamps();

            // The same bKash TrxID (or gateway id) can be used for one payment only.
            // Rows with NULL are not compared, so many unfinished payments are fine.
            $table->unique(['ref_scope', 'gateway_ref']);
            // "My payments" and the member's daily or hourly checkout limits.
            $table->index(['user_id', 'created_at']);
            // Staff queue (under_review first) and the stale-payment cleanup.
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
