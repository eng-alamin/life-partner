<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A member's saved filter set. user_id is never mass-assigned; rows are created
 * through the user's relation. `filters` is re-validated by SearchCriteria
 * every time it is loaded, so stored data is never trusted.
 */
class SavedSearch extends Model
{
    protected $fillable = ['name', 'filters'];

    protected function casts(): array
    {
        return ['filters' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
