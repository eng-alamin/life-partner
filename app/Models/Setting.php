<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One saved site setting (key and text value). Nothing is mass-assignable; SaveSettings fills
 * every column. Read settings through App\Services\Settings\SiteSettings, never directly.
 */
class Setting extends Model
{
    /** @var list<string> */
    protected $fillable = [];
}
