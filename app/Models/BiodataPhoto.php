<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PhotoStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One photo of a biodata. Nothing is mass-assignable: rows are written only by
 * the photo actions with forceFill(). File paths never leave the server; pages
 * use the authorized route `photos.show` with the random `code`.
 */
class BiodataPhoto extends Model
{
    protected $fillable = [];

    /** @var list<string> */
    protected $hidden = ['path', 'thumb_path', 'rejection_reason'];

    protected function casts(): array
    {
        return [
            'status' => PhotoStatus::class,
            'is_main' => 'boolean',
            'reviewed_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    public function biodata(): BelongsTo
    {
        return $this->belongsTo(Biodata::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** @return list<string> files on the private disk */
    public function filePaths(): array
    {
        return [$this->path, $this->thumb_path];
    }
}
