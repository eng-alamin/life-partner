<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            // One of the names in App\Enums\SettingKey. Nothing else is ever stored.
            $table->string('key', 60)->unique();
            // Text only. A switch is stored as "1" or "0". Empty means "not set".
            $table->text('value')->nullable();
            // The staff member who saved it last. A plain id on purpose (no foreign key), like users.suspended_by.
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
