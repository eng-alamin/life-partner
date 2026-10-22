<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Division extends Model
{
    public $timestamps = false;

    protected $fillable = ['name_en', 'name_bn'];

    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }

    /** Name in the current locale. */
    public function name(): string
    {
        return app()->getLocale() === 'bn' ? $this->name_bn : $this->name_en;
    }
}
