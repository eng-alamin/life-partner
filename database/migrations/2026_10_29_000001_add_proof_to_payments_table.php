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
            // Manual bKash only: file name of the member's payment screenshot on the
            // private "payment_proofs" disk. NULL when the member gave a TrxID only.
            $table->string('proof_path', 80)->nullable()->after('payer_number');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn('proof_path');
        });
    }
};
