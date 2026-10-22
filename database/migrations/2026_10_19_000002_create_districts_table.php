<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('districts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('division_id')->constrained()->restrictOnDelete();
            $table->string('name_en', 60);
            $table->string('name_bn', 60);

            $table->unique(['division_id', 'name_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('districts');
    }
};
