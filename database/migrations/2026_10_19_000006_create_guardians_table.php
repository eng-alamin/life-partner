<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guardians', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('biodata_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('relation', 20);
            // Stored encrypted (Laravel "encrypted" cast), so it needs a text column.
            $table->text('phone');
            $table->boolean('can_approve')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardians');
    }
};
