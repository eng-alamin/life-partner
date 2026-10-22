<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Laravel's standard notifications table. The "data" column holds only a
        // notification type and a few short codes, never personal text.
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->string('notifiable_type', 100);
            $table->unsignedBigInteger('notifiable_id');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // "How many unread?" and "newest first" for one member.
            $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'notifications_owner_read_index');
            $table->index(['notifiable_type', 'notifiable_id', 'created_at'], 'notifications_owner_created_index');
            $table->index('read_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
