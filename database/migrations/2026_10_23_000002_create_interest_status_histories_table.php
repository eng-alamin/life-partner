<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interest_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('interest_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            // Who caused the change: sender, receiver, guardian or system.
            $table->string('actor_type', 20);
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['interest_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interest_status_histories');
    }
};
