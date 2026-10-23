<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Biodata;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

/**
 * Turns activity log records into plain rows for the admin page.
 *
 * Only a fixed list of property keys is shown, so a key added to the log in the future
 * is never shown by accident. The page shows no name, email, phone, biodata content
 * or free text: only codes, statuses, numbers and ids. The people behind a record are
 * looked up with a few small queries (ids only), never one query per row.
 */
final class ActivityLogPresenter
{
    /** The log names the page can filter by (the first argument of activity()). */
    public const LOGS = [
        'account', 'biodata', 'billing', 'block', 'identity', 'interest', 'moderation', 'notification',
        'page', 'payment', 'plan', 'report', 'settings', 'staff', 'story', 'user',
    ];

    /** Property keys that are safe to show. Everything else is dropped. */
    private const SHOWN_KEYS = [
        'action', 'amount_minor', 'also_resolved', 'biodata_code', 'biodata_from', 'biodata_to', 'by',
        'credits', 'credits_removed', 'days', 'deleted', 'fields', 'from', 'interest_code', 'is_published',
        'guardian_approval', 'identity_code', 'keys', 'kind', 'payment_code', 'photo_code', 'plan_code', 'public_code', 'reason',
        'registration_open', 'report_code', 'reported_code', 'revision', 'role', 'side',
        'slug', 'story_code', 'subscription_cancelled', 'target_code', 'to',
    ];

    private const MAX_VALUE_LENGTH = 60;

    /**
     * @param  Collection<int, Activity>  $activities
     * @return array<int, array<string, mixed>>
     */
    public function rows(Collection $activities): array
    {
        $userIds = $activities
            ->filter(fn (Activity $activity): bool => $activity->causer_type === User::class && $activity->causer_id !== null)
            ->pluck('causer_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        $staffIds = $userIds === []
            ? []
            : array_flip(User::query()->whereIn('id', $userIds)->whereHas('roles')->pluck('id')->map(fn ($id): int => (int) $id)->all());

        $codes = $userIds === []
            ? collect()
            : Biodata::withTrashed()->whereIn('user_id', $userIds)->pluck('public_code', 'user_id');

        $rows = [];

        foreach ($activities as $activity) {
            $rows[] = [
                'id' => (int) $activity->getKey(),
                'time' => $activity->created_at,
                'log' => (string) $activity->log_name,
                'event' => $this->eventLabel((string) $activity->description),
                'causer' => $this->causer($activity, $staffIds, $codes),
                'subject' => $this->subject($activity),
                'details' => $this->details($activity),
            ];
        }

        return $rows;
    }

    private function eventLabel(string $description): string
    {
        $key = 'admin_activity.events.'.$description;

        return Lang::has($key) ? (string) __($key) : $description;
    }

    /**
     * @param  array<int, int>  $staffIds  user ids as array keys
     * @param  Collection<int, string>  $codes  public codes by user id
     * @return array{kind: string, label: string}
     */
    private function causer(Activity $activity, array $staffIds, Collection $codes): array
    {
        if ($activity->causer_type !== User::class || $activity->causer_id === null) {
            return ['kind' => 'system', 'label' => (string) __('admin_activity.causer.system')];
        }

        $id = (int) $activity->causer_id;

        if (isset($staffIds[$id])) {
            return ['kind' => 'staff', 'label' => (string) __('admin_activity.causer.staff', ['id' => $id])];
        }

        $code = $codes[$id] ?? null;

        return [
            'kind' => 'member',
            'label' => $code !== null
                ? (string) __('admin_activity.causer.member_code', ['code' => $code])
                : (string) __('admin_activity.causer.member', ['id' => $id]),
        ];
    }

    /** Type and id only, for example "Biodata #12". */
    private function subject(Activity $activity): ?string
    {
        if ($activity->subject_type === null || $activity->subject_id === null) {
            return null;
        }

        return class_basename((string) $activity->subject_type).' #'.(int) $activity->subject_id;
    }

    /** @return array<string, string> shown key => text */
    private function details(Activity $activity): array
    {
        $properties = $activity->properties?->toArray() ?? [];
        $details = [];

        foreach (self::SHOWN_KEYS as $key) {
            if (! array_key_exists($key, $properties)) {
                continue;
            }

            $text = $this->text($properties[$key]);

            if ($text !== null) {
                $details[$key] = $text;
            }
        }

        return $details;
    }

    private function text(mixed $value): ?string
    {
        if (is_bool($value)) {
            return (string) __($value ? 'admin_activity.yes' : 'admin_activity.no');
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_string($value)) {
            return Str::limit($value, self::MAX_VALUE_LENGTH);
        }

        // A plain list of numbers or words (for example the changed field names). Anything nested is dropped.
        if (is_array($value) && array_is_list($value) && $value !== [] && count(array_filter($value, 'is_scalar')) === count($value)) {
            return Str::limit(implode(', ', array_map('strval', $value)), self::MAX_VALUE_LENGTH);
        }

        return null;
    }
}
