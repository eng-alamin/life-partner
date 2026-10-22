<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('divisions', function (Blueprint $table): void {
            $table->id();
            $table->string('name_en', 60)->unique();
            $table->string('name_bn', 60);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('divisions');
    }
};
