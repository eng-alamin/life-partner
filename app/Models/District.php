<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class District extends Model
{
    public $timestamps = false;

    protected $fillable = ['division_id', 'name_en', 'name_bn'];

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    /** Name in the current locale. */
    public function name(): string
    {
        return app()->getLocale() === 'bn' ? $this->name_bn : $this->name_en;
    }
}
