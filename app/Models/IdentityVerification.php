<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\IdentityDocumentType;
use App\Enums\IdentityRejectionReason;
use App\Enums\IdentityStatus;
use Database\Factories\IdentityVerificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * One request to verify a member's identity with a picture of a document.
 *
 * Nothing is mass-assignable: rows are written only by the identity actions.
 * The picture file names are hidden from serialization. The files live on the private
 * "identity_documents" disk and are deleted after the decision. No document number is stored.
 */
class IdentityVerification extends Model
{
    /** @use HasFactory<IdentityVerificationFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [];

    /** @var list<string> */
    protected $hidden = ['front_path', 'back_path'];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'pending',
    ];

    protected static function booted(): void
    {
        static::creating(function (IdentityVerification $verification): void {
            if (blank($verification->code)) {
                $verification->code = Str::lower(Str::random(24));
            }
        });
    }

    protected function casts(): array
    {
        return [
            'status' => IdentityStatus::class,
            'document_type' => IdentityDocumentType::class,
            'rejection_reason' => IdentityRejectionReason::class,
            'open_key' => 'integer',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'files_deleted_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === IdentityStatus::Pending;
    }
}
