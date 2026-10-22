<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('blocker_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('blocked_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            // One block per pair. The unique key also serves "who did I block" lookups.
            $table->unique(['blocker_user_id', 'blocked_user_id']);
            // "Who blocked me" lookups.
            $table->index('blocked_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocks');
    }
};
