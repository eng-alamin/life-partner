<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per SMS we try to send. It stores NO phone number and NO message
        // text: only who, which kind, and what the provider answered. It is used for
        // the daily limit and for finding delivery problems.
        Schema::create('sms_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('status', 12)->default('queued');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('provider_ref', 40)->nullable();
            $table->smallInteger('error_code')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_messages');
    }
};
