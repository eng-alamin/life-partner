<?php

declare(strict_types=1);

namespace App\Services\Search;

use App\Enums\BiodataType;
use App\Enums\EducationLevel;
use App\Enums\MaritalStatus;
use App\Enums\PracticingLevel;
use App\Support\Countries;
use App\Support\Digits;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * A validated set of search filters. Nothing outside this class builds filters
 * from browser or database input: URL parameters, form fields and saved
 * searches all go through rules() or lenient(), so every value that reaches the
 * query is already checked (enums, ranges, existing district, safe text).
 */
final class SearchCriteria
{
    /** Filter names, shared by the page properties, the URL and saved searches. */
    public const KEYS = [
        'type', 'age_min', 'age_max', 'height_min', 'height_max', 'marital_status',
        'country', 'district_id', 'min_education', 'profession', 'practicing_level',
    ];

    private const TEXT_PATTERN = '/^[\p{L}\p{M}\p{N} .,\x27\x{2019}()&\/-]+$/u';

    public function __construct(
        public readonly ?BiodataType $type = null,
        public readonly ?int $ageMin = null,
        public readonly ?int $ageMax = null,
        public readonly ?int $heightMin = null,
        public readonly ?int $heightMax = null,
        public readonly ?MaritalStatus $maritalStatus = null,
        public readonly ?string $country = null,
        public readonly ?int $districtId = null,
        public readonly ?EducationLevel $minEducation = null,
        public readonly ?string $profession = null,
        public readonly ?PracticingLevel $practicingLevel = null,
    ) {}

    /** @return array<string, array<int, mixed>> */
    public static function rules(): array
    {
        return [
            'type' => ['nullable', Rule::enum(BiodataType::class)],
            'age_min' => ['nullable', 'integer', 'between:18,80'],
            'age_max' => ['nullable', 'integer', 'between:18,80'],
            'height_min' => ['nullable', 'integer', 'between:100,230'],
            'height_max' => ['nullable', 'integer', 'between:100,230'],
            'marital_status' => ['nullable', Rule::enum(MaritalStatus::class)],
            'country' => ['nullable', Rule::in(Countries::codes())],
            'district_id' => ['nullable', 'integer', Rule::exists('districts', 'id')],
            'min_education' => ['nullable', Rule::enum(EducationLevel::class)],
            'profession' => ['nullable', 'string', 'max:60', 'regex:'.self::TEXT_PATTERN],
            'practicing_level' => ['nullable', Rule::enum(PracticingLevel::class)],
        ];
    }

    /**
     * Builds criteria from untrusted input. Fields that fail validation are
     * dropped instead of raising an error, so an old saved search or a hand-edited
     * URL never breaks the page.
     *
     * @param  array<string, mixed>  $input
     */
    public static function lenient(array $input): self
    {
        $input = self::normalize($input);

        $failed = array_keys(Validator::make($input, self::rules())->errors()->messages());

        return self::fromValidated(Arr::except($input, $failed));
    }

    /**
     * @param  array<string, mixed>  $data  already validated with rules()
     */
    public static function fromValidated(array $data): self
    {
        $data = self::normalize($data);

        $ageMin = self::int($data['age_min'] ?? null);
        $ageMax = self::int($data['age_max'] ?? null);
        $heightMin = self::int($data['height_min'] ?? null);
        $heightMax = self::int($data['height_max'] ?? null);

        // A reversed range is turned around instead of returning nothing.
        if ($ageMin !== null && $ageMax !== null && $ageMin > $ageMax) {
            [$ageMin, $ageMax] = [$ageMax, $ageMin];
        }

        if ($heightMin !== null && $heightMax !== null && $heightMin > $heightMax) {
            [$heightMin, $heightMax] = [$heightMax, $heightMin];
        }

        return new self(
            type: BiodataType::tryFrom((string) ($data['type'] ?? '')),
            ageMin: $ageMin,
            ageMax: $ageMax,
            heightMin: $heightMin,
            heightMax: $heightMax,
            maritalStatus: MaritalStatus::tryFrom((string) ($data['marital_status'] ?? '')),
            country: isset($data['country']) && Countries::exists((string) $data['country']) ? (string) $data['country'] : null,
            districtId: self::int($data['district_id'] ?? null),
            minEducation: EducationLevel::tryFrom((string) ($data['min_education'] ?? '')),
            profession: isset($data['profession']) && $data['profession'] !== '' ? (string) $data['profession'] : null,
            practicingLevel: PracticingLevel::tryFrom((string) ($data['practicing_level'] ?? '')),
        );
    }

    /**
     * Guests may only use the type, age and district filters. Everything else
     * is dropped on the server, whatever the browser sent.
     */
    public function forGuest(): self
    {
        return new self(
            type: $this->type,
            ageMin: $this->ageMin,
            ageMax: $this->ageMax,
            districtId: $this->districtId,
        );
    }

    /**
     * Plain values for the page properties, the URL and saved searches.
     * The type is left out of saved searches: a member's type comes from their
     * own biodata, never from stored data.
     *
     * @return array<string, string>
     */
    public function toArray(bool $forStorage = false): array
    {
        $values = [
            'type' => $this->type?->value,
            'age_min' => $this->ageMin,
            'age_max' => $this->ageMax,
            'height_min' => $this->heightMin,
            'height_max' => $this->heightMax,
            'marital_status' => $this->maritalStatus?->value,
            'country' => $this->country,
            'district_id' => $this->districtId,
            'min_education' => $this->minEducation?->value,
            'profession' => $this->profession,
            'practicing_level' => $this->practicingLevel?->value,
        ];

        if ($forStorage) {
            unset($values['type']);
        }

        return array_map('strval', array_filter($values, fn ($value): bool => $value !== null));
    }

    /**
     * Only known keys, scalar values, trimmed text, Bangla digits turned into
     * ASCII digits, and blank values removed.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, string>
     */
    private static function normalize(array $input): array
    {
        $clean = [];

        foreach (self::KEYS as $key) {
            $value = $input[$key] ?? null;

            if (! is_scalar($value)) {
                continue;
            }

            $value = (string) $value;

            if ($key !== 'profession') {
                $value = (string) Digits::normalize($value);
            }

            $value = trim((string) preg_replace('/\s+/u', ' ', $value));

            if ($value !== '') {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }

    private static function int(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}
