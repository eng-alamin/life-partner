<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biodata_photos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('biodata_id')->constrained()->cascadeOnDelete();
            // Random public handle used in photo URLs. Never a numeric id.
            $table->string('code', 24)->unique();
            // Paths on the private "photos" disk, never a public URL.
            $table->string('path', 120);
            $table->string('thumb_path', 120);
            $table->boolean('is_main')->default(false);
            $table->string('status', 20)->default('pending');
            $table->string('rejection_reason', 200)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['biodata_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biodata_photos');
    }
};
