<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EducationLevel;
use App\Enums\MaritalStatus;
use App\Enums\PracticingLevel;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiodataPreference extends Model
{
    protected $fillable = [
        'age_min', 'age_max', 'height_min_cm', 'min_education_level',
        'preferred_country', 'preferred_district_id', 'marital_statuses', 'min_practicing_level',
    ];

    protected function casts(): array
    {
        return [
            'min_education_level' => EducationLevel::class,
            'min_practicing_level' => PracticingLevel::class,
            'marital_statuses' => AsEnumCollection::of(MaritalStatus::class),
        ];
    }

    public function biodata(): BelongsTo
    {
        return $this->belongsTo(Biodata::class);
    }

    public function preferredDistrict(): BelongsTo
    {
        return $this->belongsTo(District::class, 'preferred_district_id');
    }
}
