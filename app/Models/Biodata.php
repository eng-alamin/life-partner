<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BiodataStatus;
use App\Enums\BiodataType;
use App\Enums\BloodGroup;
use App\Enums\Complexion;
use App\Enums\DressPractice;
use App\Enums\FamilyFinancialStatus;
use App\Enums\HiddenSource;
use App\Enums\IncomeRange;
use App\Enums\LivingArrangement;
use App\Enums\Madhhab;
use App\Enums\MaritalStatus;
use App\Enums\MarriageTimeline;
use App\Enums\ParentStatus;
use App\Enums\PhotoStatus;
use App\Enums\PhotoVisibility;
use App\Enums\PracticingLevel;
use App\Enums\PrayerHabit;
use App\Enums\QuranReading;
use App\Enums\SpouseWorkAfterMarriage;
use App\Enums\UserStatus;
use App\Services\Biodata\PublicCodeGenerator;
use Database\Factories\BiodataFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Biodata extends Model
{
    /** @use HasFactory<BiodataFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Only the fields a member fills in. user_id, public_code, status,
     * rejection_reason, submitted_at, reviewed_by, reviewed_at, revision, hidden_at, hidden_source,
     * hidden_from, hidden_revision, hidden_by, deactivated_at, deactivation_reason,
     * highest_education_rank, photo_visibility, identity_verified_at and completion_pct are changed only by server-side code, never mass-assigned.
     */
    protected $fillable = [
        'type', 'marital_status', 'birth_date', 'height_cm', 'weight_kg', 'complexion', 'blood_group',
        'present_country', 'present_district_id', 'present_city', 'permanent_district_id',
        'profession', 'employer', 'income_range',
        'prayer_habit', 'quran_reading', 'madhhab', 'practicing_level', 'dress_practice',
        'father_status', 'mother_status', 'brothers_count', 'sisters_count', 'family_financial_status',
        'hobbies', 'about_me',
        'marriage_timeline', 'spouse_work_after_marriage', 'living_arrangement',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'draft',
        'completion_pct' => 0,
        'revision' => 0,
        'photo_visibility' => 'hidden',
    ];

    protected static function booted(): void
    {
        static::creating(function (Biodata $biodata): void {
            if (blank($biodata->public_code)) {
                $biodata->public_code = app(PublicCodeGenerator::class)->generate();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'status' => BiodataStatus::class,
            'type' => BiodataType::class,
            'marital_status' => MaritalStatus::class,
            'birth_date' => 'date:Y-m-d',
            'complexion' => Complexion::class,
            'blood_group' => BloodGroup::class,
            'income_range' => IncomeRange::class,
            'prayer_habit' => PrayerHabit::class,
            'quran_reading' => QuranReading::class,
            'madhhab' => Madhhab::class,
            'practicing_level' => PracticingLevel::class,
            'dress_practice' => DressPractice::class,
            'father_status' => ParentStatus::class,
            'mother_status' => ParentStatus::class,
            'family_financial_status' => FamilyFinancialStatus::class,
            'marriage_timeline' => MarriageTimeline::class,
            'spouse_work_after_marriage' => SpouseWorkAfterMarriage::class,
            'living_arrangement' => LivingArrangement::class,
            'photo_visibility' => PhotoVisibility::class,
            'hidden_source' => HiddenSource::class,
            'hidden_at' => 'datetime',
            'hidden_revision' => 'integer',
            'deactivated_at' => 'datetime',
            'identity_verified_at' => 'datetime',
            'revision' => 'integer',
            'highest_education_rank' => 'integer',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** The "identity verified" badge. Set and cleared only by the identity actions. */
    public function isIdentityVerified(): bool
    {
        return $this->identity_verified_at !== null;
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function educations(): HasMany
    {
        return $this->hasMany(BiodataEducation::class)->orderBy('sort_order');
    }

    public function preference(): HasOne
    {
        return $this->hasOne(BiodataPreference::class);
    }

    public function guardian(): HasOne
    {
        return $this->hasOne(Guardian::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(BiodataPhoto::class)->orderBy('sort_order')->orderBy('id');
    }

    /** The main photo, only if it is approved. Used for cards. */
    public function mainPhoto(): HasOne
    {
        return $this->hasOne(BiodataPhoto::class)
            ->where('is_main', true)
            ->where('status', PhotoStatus::Approved->value);
    }

    /** Interests this biodata has sent. */
    public function sentInterests(): HasMany
    {
        return $this->hasMany(Interest::class, 'sender_biodata_id');
    }

    /** Interests this biodata has received. */
    public function receivedInterests(): HasMany
    {
        return $this->hasMany(Interest::class, 'receiver_biodata_id');
    }

    public function presentDistrict(): BelongsTo
    {
        return $this->belongsTo(District::class, 'present_district_id');
    }

    public function permanentDistrict(): BelongsTo
    {
        return $this->belongsTo(District::class, 'permanent_district_id');
    }

    /**
     * Only approved biodatas of members who are not suspended may appear in search results.
     * The small list of suspended members is used, so the (status, type, ...) index still works.
     */
    public function scopeSearchable(Builder $query): Builder
    {
        return $query
            ->where('status', BiodataStatus::Approved->value)
            ->whereNotIn('user_id', User::query()->where('status', UserStatus::Suspended->value)->select('id'));
    }

    /** False when the owner is suspended. One small query: use it for a single biodata, never inside a list. */
    public function ownerIsActive(): bool
    {
        return User::query()
            ->whereKey($this->user_id)
            ->where('status', UserStatus::Active->value)
            ->exists();
    }

    public function getAgeAttribute(): ?int
    {
        return $this->birth_date?->age;
    }

    public function getRouteKeyName(): string
    {
        return 'public_code';
    }
}
