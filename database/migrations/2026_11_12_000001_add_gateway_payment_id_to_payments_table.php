<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            // The gateway's id of the checkout session (bKash "paymentID"). Different from gateway_ref,
            // which is the final transaction id (bKash "trxID") and exists only after the money moved.
            // The callback finds our payment by this id, so it must be unique.
            $table->string('gateway_payment_id', 64)->nullable()->unique()->after('gateway_ref');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropUnique(['gateway_payment_id']);
            $table->dropColumn('gateway_payment_id');
        });
    }
};
