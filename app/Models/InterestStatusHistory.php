<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InterestActor;
use App\Enums\InterestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One row per status change of an interest. Written only by the interest actions. */
class InterestStatusHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'from_status' => InterestStatus::class,
            'to_status' => InterestStatus::class,
            'actor_type' => InterestActor::class,
            'created_at' => 'datetime',
        ];
    }

    public function interest(): BelongsTo
    {
        return $this->belongsTo(Interest::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
