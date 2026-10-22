<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // The number is stored encrypted (the User model casts it). It cannot be
            // searched, so the unique, searchable check uses phone_hash instead.
            $table->text('phone')->nullable()->after('email_verified_at');

            // HMAC of the number. Filled only AFTER the number is verified, so a
            // member cannot "reserve" someone else's number by typing it.
            $table->string('phone_hash', 64)->nullable()->unique()->after('phone');

            $table->timestamp('phone_verified_at')->nullable()->after('phone_hash');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['phone_hash']);
            $table->dropColumn(['phone', 'phone_hash', 'phone_verified_at']);
        });
    }
};
