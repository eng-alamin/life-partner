<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CreditReason;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * One line of a member's credit history. The ledger is append-only: a row is
 * never edited or deleted. The balance is always computed from the rows
 * (see CreditBalance); there is no stored balance that could drift.
 */
class CreditLedgerEntry extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'credit_ledger';

    /** @var list<string> */
    protected $fillable = [];

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('The credit ledger is append-only: rows cannot be edited.');
        });

        static::deleting(function (): never {
            throw new LogicException('The credit ledger is append-only: rows cannot be deleted.');
        });
    }

    protected function casts(): array
    {
        return [
            'delta' => 'integer',
            'reason' => CreditReason::class,
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function interest(): BelongsTo
    {
        return $this->belongsTo(Interest::class);
    }
}
