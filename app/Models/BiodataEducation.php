<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EducationLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiodataEducation extends Model
{
    protected $table = 'biodata_educations';

    protected $fillable = ['degree', 'level', 'institution', 'passing_year', 'sort_order'];

    protected function casts(): array
    {
        return ['level' => EducationLevel::class];
    }

    public function biodata(): BelongsTo
    {
        return $this->belongsTo(Biodata::class);
    }
}
