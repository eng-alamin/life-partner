<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table): void {
            $table->id();
            // Random public handle used in admin URLs. Never a numeric id.
            $table->string('code', 24)->unique();

            // Set only when a signed-in member wrote the message. The id comes from the server session, never from the form.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Personal text, stored encrypted (the model casts them), so they need text columns.
            $table->text('name');
            $table->text('email');
            $table->text('phone')->nullable();
            $table->text('subject');
            $table->text('message');

            // new, handled, spam
            $table->string('status', 20)->default('new');

            // HMAC of the sender's IP address: lets staff spot a spammer without storing the address.
            $table->string('ip_hash', 64)->nullable();

            // Filled by staff in the inbox.
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();

            $table->timestamps();

            // Inbox: newest first, filtered by status.
            $table->index(['status', 'created_at']);
            // The retention clean-up looks at the age only.
            $table->index('created_at');
            // "Other messages from the same sender" lookups.
            $table->index('ip_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};
