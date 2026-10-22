<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table): void {
            $table->id();
            // Stable slug such as "credits-10" or "monthly". Used in URLs and snapshots.
            $table->string('code', 40)->unique();
            // credit_pack or membership.
            $table->string('kind', 20);

            $table->string('name_bn', 120);
            $table->string('name_en', 120);
            $table->string('description_bn', 500)->nullable();
            $table->string('description_en', 500)->nullable();

            // Money is an integer in poisha (1 BDT = 100 poisha). Never a fractional number type.
            $table->unsignedInteger('price_minor');
            $table->char('currency', 3)->default('BDT');

            // Contact unlock credits given by this plan (0 for a pure membership).
            $table->unsignedSmallInteger('credits')->default(0);
            // Membership only: length of the period and the daily interest limit it gives.
            $table->unsignedSmallInteger('duration_days')->nullable();
            $table->unsignedSmallInteger('daily_interest_limit')->nullable();

            $table->boolean('is_active')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            // Public plans page: active plans in order.
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
