<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** "user_id saved biodata_id for later". Private to its owner. Written only by ManageShortlist. */
class Shortlist extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }
}
