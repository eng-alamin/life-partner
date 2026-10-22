<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biodata_educations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('biodata_id')->constrained()->cascadeOnDelete();
            $table->string('degree', 120);
            $table->string('institution', 150);
            $table->unsignedSmallInteger('passing_year');
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['biodata_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biodata_educations');
    }
};
