<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PlanKind;
use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Something a member can buy: a credit pack or a membership period. Prices are
 * integers in poisha (1 BDT = 100 poisha). A plan that was ever paid for is
 * deactivated, never deleted. Nothing is mass-assignable.
 */
class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'kind' => PlanKind::class,
            'price_minor' => 'integer',
            'credits' => 'integer',
            'duration_days' => 'integer',
            'daily_interest_limit' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    /** @param  Builder<Plan>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    public function name(): string
    {
        return app()->getLocale() === 'en' ? $this->name_en : $this->name_bn;
    }

    public function description(): ?string
    {
        return app()->getLocale() === 'en' ? $this->description_en : $this->description_bn;
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
