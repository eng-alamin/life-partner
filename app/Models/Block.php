<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * "blocker_user_id has blocked blocked_user_id". Nothing is mass-assignable:
 * rows are written only by BlockMember and removed only by UnblockMember.
 * The rule itself (a block hides both sides from each other) lives in BlockGuard.
 */
class Block extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
