<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContactMessageStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A message sent through the public contact form. Nothing is mass-assignable: rows are written only by
 * SubmitContactMessage (and the staff actions in the inbox) with forceFill(). The sender's name, email,
 * phone, the subject and the text are encrypted in the database, hidden from serialization and never
 * written to the activity log.
 */
class ContactMessage extends Model
{
    /** Messages are deleted this many days after they were sent (clean-up job). */
    public const RETENTION_DAYS = 180;

    /** @var list<string> */
    protected $fillable = [];

    /** @var list<string> */
    protected $hidden = ['name', 'email', 'phone', 'subject', 'message', 'ip_hash'];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'new',
    ];

    protected static function booted(): void
    {
        static::creating(function (ContactMessage $message): void {
            if (blank($message->code)) {
                $message->code = Str::lower(Str::random(24));
            }
        });
    }

    protected function casts(): array
    {
        return [
            'status' => ContactMessageStatus::class,
            'name' => 'encrypted',
            'email' => 'encrypted',
            'phone' => 'encrypted',
            'subject' => 'encrypted',
            'message' => 'encrypted',
            'handled_at' => 'datetime',
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

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
