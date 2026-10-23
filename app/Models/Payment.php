<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Enums\PlanKind;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * One attempt to pay for a plan. The plan's name, price, credits and days are
 * copied into the row (snapshot), so changing a plan later never rewrites history.
 * The amount is always read from our own plan row, never from a request.
 *
 * Nothing is mass-assignable: rows are written only by the billing actions.
 * The payer's phone number is hidden from serialization and never logged.
 */
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [];

    /** @var list<string> */
    protected $hidden = ['payer_number'];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'pending',
        'currency' => 'BDT',
    ];

    protected static function booted(): void
    {
        static::creating(function (Payment $payment): void {
            if (blank($payment->code)) {
                $payment->code = Str::lower(Str::random(24));
            }

            if (blank($payment->tran_id)) {
                $payment->tran_id = 'LP'.now()->format('ymd').Str::upper(Str::random(12));
            }
        });

        // Keep the unique "one payment per gateway reference" scope in step with the gateway.
        static::saving(function (Payment $payment): void {
            $payment->ref_scope = ($payment->gateway_ref !== null && $payment->gateway instanceof PaymentGateway)
                ? $payment->gateway->refScope()
                : null;
        });
    }

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'gateway' => PaymentGateway::class,
            'plan_kind' => PlanKind::class,
            'amount_minor' => 'integer',
            'credits' => 'integer',
            'duration_days' => 'integer',
            'daily_interest_limit' => 'integer',
            'submitted_at' => 'datetime',
            'paid_at' => 'datetime',
            'fulfilled_at' => 'datetime',
            'reviewed_at' => 'datetime',
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

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * bKash API payments flagged "needs attention" that staff have not marked as handled,
     * and that are not paid or refunded. The one rule behind the attention tab, the menu
     * badge and the dashboard count.
     *
     * @param  Builder<Payment>  $query
     * @return Builder<Payment>
     */
    public function scopeNeedsAttention(Builder $query): Builder
    {
        return $query
            ->where('payments.gateway', PaymentGateway::Bkash->value)
            ->whereNotIn('payments.status', [PaymentStatus::Paid->value, PaymentStatus::Refunded->value])
            ->whereHas('events', fn ($events) => $events->where('event_type', 'needs_attention'))
            ->whereDoesntHave('events', fn ($events) => $events->where('event_type', 'attention_resolved'));
    }

    public function events(): HasMany
    {
        return $this->hasMany(PaymentEvent::class)->orderBy('id');
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(CreditLedgerEntry::class);
    }
}
