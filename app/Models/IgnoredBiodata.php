<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** "user_id does not want to see biodata_id in search". One-sided: the other member is never told. Written only by ManageIgnoreList. */
class IgnoredBiodata extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'ignored_biodatas';

    protected $fillable = [];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }
}
