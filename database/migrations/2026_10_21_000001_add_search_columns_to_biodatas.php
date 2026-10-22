<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('biodata_educations', function (Blueprint $table): void {
            // Nullable so rows saved before Module 4 stay valid; the form now requires it.
            $table->string('level', 20)->nullable();
        });

        Schema::table('biodatas', function (Blueprint $table): void {
            // Highest EducationLevel::rank() of the member's degrees. Set only by SaveBiodataStep.
            $table->unsignedTinyInteger('highest_education_rank')->nullable();

            $table->index(['status', 'type', 'reviewed_at'], 'biodatas_search_recent_idx');
            $table->index(['status', 'type', 'present_country', 'present_district_id'], 'biodatas_search_place_idx');
            $table->index(['status', 'type', 'practicing_level'], 'biodatas_search_practicing_idx');
        });
    }

    public function down(): void
    {
        Schema::table('biodatas', function (Blueprint $table): void {
            $table->dropIndex('biodatas_search_recent_idx');
            $table->dropIndex('biodatas_search_place_idx');
            $table->dropIndex('biodatas_search_practicing_idx');
        });

        Schema::table('biodatas', function (Blueprint $table): void {
            $table->dropColumn('highest_education_rank');
        });

        Schema::table('biodata_educations', function (Blueprint $table): void {
            $table->dropColumn('level');
        });
    }
};
