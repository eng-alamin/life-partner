<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Records that a member already paid (with a credit) to see the contact details
 * of one interest. The unique (user_id, interest_id) key makes "one credit per
 * interest, once" a fact of the database.
 */
class ContactUnlock extends Model
{
    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $fillable = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function interest(): BelongsTo
    {
        return $this->belongsTo(Interest::class);
    }

    public function ledgerEntry(): BelongsTo
    {
        return $this->belongsTo(CreditLedgerEntry::class, 'ledger_id');
    }
}
