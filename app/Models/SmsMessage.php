<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SmsStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A record that an SMS was attempted. It never holds a phone number or message text.
 */
class SmsMessage extends Model
{
    use MassPrunable;

    /** The type value used for verification codes. */
    public const TYPE_OTP = 'otp';

    protected $fillable = ['user_id', 'type', 'status'];

    protected function casts(): array
    {
        return [
            'status' => SmsStatus::class,
            'attempts' => 'integer',
            'error_code' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Old rows are removed daily (see routes/console.php). */
    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subDays(max(7, (int) config('lifepartner.sms.keep_days'))));
    }
}
