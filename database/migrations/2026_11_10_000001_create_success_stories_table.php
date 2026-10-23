<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('success_stories', function (Blueprint $table): void {
            $table->id();
            // Random public handle, used in the public and the admin addresses. Never a numeric id.
            $table->string('code', 24)->unique();

            // How the couple is shown to the public: first names or initials only. No digits, no contact data.
            // There is NO link to a user or a biodata on purpose: a story can never lead back to an account.
            $table->string('couple_label', 60);

            $table->string('title_bn', 120);
            $table->string('title_en', 120);
            $table->text('body_bn');
            $table->text('body_en');

            // draft | published (App\Enums\StoryStatus).
            $table->string('status', 20)->default('draft');

            // The record of the consent. The proof itself is NOT stored here.
            $table->string('consent_given_by', 20)->nullable();
            $table->date('consent_given_at')->nullable();
            $table->string('consent_form', 20)->nullable();
            $table->string('consent_note', 200)->nullable();
            // When the people withdrew their consent. A story with this date can not be published again
            // until a newer consent date is recorded.
            $table->timestamp('consent_withdrawn_at')->nullable();

            $table->timestamp('published_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // The public list: published, newest first.
            $table->index(['status', 'published_at'], 'success_stories_status_published_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('success_stories');
    }
};
