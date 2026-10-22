<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biodatas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('public_code', 12)->unique();

            // Moderation. status is changed only by server-side code.
            $table->string('status', 20)->default('draft');
            $table->string('rejection_reason', 500)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedTinyInteger('completion_pct')->default(0);

            // Step 1: general. Nullable because a draft is saved step by step.
            $table->string('type', 10)->nullable();
            $table->string('marital_status', 20)->nullable();
            $table->date('birth_date')->nullable();
            $table->unsignedSmallInteger('height_cm')->nullable();
            $table->unsignedSmallInteger('weight_kg')->nullable();
            $table->string('complexion', 20)->nullable();
            $table->string('blood_group', 10)->nullable();

            // Step 2: address. "Living abroad" is derived from present_country.
            $table->char('present_country', 2)->nullable();
            $table->foreignId('present_district_id')->nullable()->constrained('districts')->nullOnDelete();
            $table->string('present_city', 100)->nullable();
            $table->foreignId('permanent_district_id')->nullable()->constrained('districts')->nullOnDelete();

            // Step 4: work. employer is never shown to other members.
            $table->string('profession', 120)->nullable();
            $table->string('employer', 150)->nullable();
            $table->string('income_range', 30)->nullable();

            // Step 5: religion.
            $table->string('prayer_habit', 30)->nullable();
            $table->string('quran_reading', 30)->nullable();
            $table->string('madhhab', 30)->nullable();
            $table->string('practicing_level', 30)->nullable();
            $table->string('dress_practice', 30)->nullable();

            // Step 6: family.
            $table->string('father_status', 20)->nullable();
            $table->string('mother_status', 20)->nullable();
            $table->unsignedTinyInteger('brothers_count')->nullable();
            $table->unsignedTinyInteger('sisters_count')->nullable();
            $table->string('family_financial_status', 30)->nullable();

            // Step 7: personal.
            $table->string('hobbies', 255)->nullable();
            $table->text('about_me')->nullable();

            // Step 8: marriage.
            $table->string('marriage_timeline', 30)->nullable();
            $table->string('spouse_work_after_marriage', 20)->nullable();
            $table->string('living_arrangement', 30)->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Search patterns (Module 4).
            $table->index(['status', 'type', 'birth_date']);
            $table->index(['status', 'type', 'marital_status']);
            $table->index(['present_country', 'present_district_id']);
            $table->index('practicing_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biodatas');
    }
};
