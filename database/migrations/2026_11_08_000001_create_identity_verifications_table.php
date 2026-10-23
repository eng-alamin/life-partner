<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identity_verifications', function (Blueprint $table): void {
            $table->id();
            // Random public code used in addresses and logs. The row id is never shown.
            $table->string('code', 24)->unique();

            // A member's requests go when the account is erased. The files are deleted by code (see the purge).
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // pending | approved | rejected (App\Enums\IdentityStatus).
            $table->string('status', 20)->default('pending');
            // nid | passport | driving_license (App\Enums\IdentityDocumentType).
            $table->string('document_type', 20);

            // Names of the stored image files on the private "identity_documents" disk.
            // Emptied when the files are deleted after the decision. No document number is ever stored.
            $table->string('front_path', 255)->nullable();
            $table->string('back_path', 255)->nullable();
            $table->timestamp('files_deleted_at')->nullable();

            // Equals user_id while the request is pending, and is null after the decision. The unique
            // index then allows only ONE open request per member, even when two requests arrive together.
            $table->unsignedBigInteger('open_key')->nullable()->unique();

            // useCurrent() gives the column a real default, so MySQL servers that run with
            // explicit_defaults_for_timestamp = OFF do not reject the table (error 1067).
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            // One value of App\Enums\IdentityRejectionReason, never free text.
            $table->string('rejection_reason', 30)->nullable();

            $table->timestamps();

            // The staff queue (oldest pending first) and the "3 per day" limit.
            $table->index(['status', 'submitted_at'], 'identity_verifications_status_submitted_index');
            $table->index(['user_id', 'submitted_at'], 'identity_verifications_user_submitted_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identity_verifications');
    }
};