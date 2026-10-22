<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table): void {
            $table->id();
            // Fixed list of slugs kept in code (App\Models\Page::SLUGS). Used in the public address.
            $table->string('slug', 40)->unique();

            $table->string('title_bn', 120);
            $table->string('title_en', 120);
            // Plain text with a few markers (## heading, - list). Never HTML. 15,000 characters at most.
            $table->text('body_bn');
            $table->text('body_en');

            // An unpublished page gives a 404 to the public and has no menu link.
            $table->boolean('is_published')->default(false);

            // The staff member who saved it last. Kept as a plain link; set to null if that account is erased.
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
