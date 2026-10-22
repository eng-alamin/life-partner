<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biodata_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('biodata_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('age_min')->nullable();
            $table->unsignedTinyInteger('age_max')->nullable();
            $table->unsignedSmallInteger('height_min_cm')->nullable();
            $table->string('min_education_level', 20)->nullable();
            $table->char('preferred_country', 2)->nullable();
            $table->foreignId('preferred_district_id')->nullable()->constrained('districts')->nullOnDelete();
            $table->json('marital_statuses')->nullable();
            $table->string('min_practicing_level', 30)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biodata_preferences');
    }
};
