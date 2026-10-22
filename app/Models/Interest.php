<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GuardianApprovalStatus;
use App\Enums\InterestStatus;
use Database\Factories\InterestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * An interest request from one biodata to another. Nothing is mass-assignable:
 * rows are written only by the interest actions with forceFill(). Pages and
 * links use the random `code`, never the numeric id.
 */
class Interest extends Model
{
    /** @use HasFactory<InterestFactory> */
    use HasFactory;

    protected $fillable = [];

    /** @var list<string> */
    protected $hidden = ['message', 'guardian_token_hash', 'guardian_token_expires_at', 'pending_pair'];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'pending',
        'guardian_status' => 'not_required',
    ];

    protected static function booted(): void
    {
        static::creating(function (Interest $interest): void {
            if (blank($interest->code)) {
                $interest->code = Str::lower(Str::random(24));
            }
        });

        // Keep the unique "one open request per pair" key in step with the status.
        static::saving(function (Interest $interest): void {
            $interest->pending_pair = $interest->status === InterestStatus::Pending
                ? $interest->sender_biodata_id.'-'.$interest->receiver_biodata_id
                : null;
        });
    }

    protected function casts(): array
    {
        return [
            'status' => InterestStatus::class,
            'guardian_status' => GuardianApprovalStatus::class,
            'guardian_decided_at' => 'datetime',
            'guardian_token_expires_at' => 'datetime',
            'responded_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(Biodata::class, 'sender_biodata_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(Biodata::class, 'receiver_biodata_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(InterestStatusHistory::class)->orderBy('id');
    }
}
