<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GuardianRelation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Guardian extends Model
{
    /** Guardian details are private: nothing here is serialised by accident. */
    protected $fillable = ['name', 'relation', 'phone'];

    protected $hidden = ['name', 'phone'];

    protected function casts(): array
    {
        return [
            'relation' => GuardianRelation::class,
            // Encrypted at rest with the application key.
            'phone' => 'encrypted',
            'can_approve' => 'boolean',
        ];
    }

    public function biodata(): BelongsTo
    {
        return $this->belongsTo(Biodata::class);
    }
}
