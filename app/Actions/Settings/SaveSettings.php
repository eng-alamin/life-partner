<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Enums\SettingKey;
use App\Models\Setting;
use App\Models\User;
use App\Services\Settings\SiteSettings;
use App\Support\Digits;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Saves the site settings. Everything is checked here on the server.
 *
 * - Only an active admin holding settings.manage may save (SettingPolicy).
 * - Only the names in SettingKey exist. Any other key in the input is ignored.
 * - Email: valid address, 120 characters at most. Phone: digits with an optional leading +,
 *   spaces and hyphens, 7 to 15 digits (Bangla digits are converted). Address: one line, 200 at most.
 *   Empty means "not set" and is not shown to the public.
 * - guardian_approval is a switch too. If the input does not hold it, the current value is kept.
 * - Only changed values are written. The activity log keeps the names of the changed settings and,
 *   for the two switches, their new value. It never holds the email, phone or address.
 */
final class SaveSettings
{
    public const EMAIL_MAX = 120;

    public const ADDRESS_MAX = 200;

    public function __construct(private readonly SiteSettings $settings)
    {
    }

    /** @param  array<string, mixed>  $input  registration_open, guardian_approval, contact_email, contact_phone, contact_address_bn, contact_address_en */
    public function __invoke(User $staff, array $input): void
    {
        Gate::forUser($staff)->authorize('update', new Setting);

        $data = $this->validated($input);

        $changed = DB::transaction(function () use ($staff, $data): array {
            $rows = Setting::query()->lockForUpdate()->get()->keyBy('key');
            $changed = [];

            foreach (SettingKey::cases() as $key) {
                $new = $data[$key->value];
                $row = $rows->get($key->value);
                $current = $this->blankToNull($row !== null ? $row->value : $key->default());

                if ($current === $new) {
                    continue;
                }

                $row ??= new Setting;
                $row->forceFill(['key' => $key->value, 'value' => $new, 'updated_by' => $staff->getKey()])->save();

                $changed[] = $key->value;
            }

            if ($changed !== []) {
                $properties = ['keys' => $changed];

                if (in_array(SettingKey::RegistrationOpen->value, $changed, true)) {
                    $properties['registration_open'] = $data[SettingKey::RegistrationOpen->value] === '1';
                }

                if (in_array(SettingKey::GuardianApproval->value, $changed, true)) {
                    $properties['guardian_approval'] = $data[SettingKey::GuardianApproval->value] === '1';
                }

                activity('settings')
                    ->causedBy($staff)
                    ->withProperties($properties)
                    ->log('settings.updated');
            }

            return $changed;
        });

        if ($changed !== []) {
            $this->settings->forget();
        }
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, ?string>  One entry for every SettingKey.
     */
    private function validated(array $input): array
    {
        $data = [];
        $errors = [];

        $data[SettingKey::RegistrationOpen->value] = filter_var($input['registration_open'] ?? false, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';

        $data[SettingKey::GuardianApproval->value] = array_key_exists('guardian_approval', $input)
            ? (filter_var($input['guardian_approval'], FILTER_VALIDATE_BOOLEAN) ? '1' : '0')
            : $this->currentGuardianApproval();

        $email = Str::lower($this->line($input['contact_email'] ?? ''));

        if ($email !== '' && (mb_strlen($email) > self::EMAIL_MAX || filter_var($email, FILTER_VALIDATE_EMAIL) === false)) {
            $errors['contact_email'] = __('admin_settings.errors.email_invalid');
        }

        $data[SettingKey::ContactEmail->value] = $this->blankToNull($email);

        $phone = $this->line(Digits::normalize($this->text($input['contact_phone'] ?? '')));

        if ($phone !== '' && ! $this->phoneIsValid($phone)) {
            $errors['contact_phone'] = __('admin_settings.errors.phone_invalid');
        }

        $data[SettingKey::ContactPhone->value] = $this->blankToNull($phone);

        foreach (['contact_address_bn' => SettingKey::ContactAddressBn, 'contact_address_en' => SettingKey::ContactAddressEn] as $field => $key) {
            $address = $this->line($input[$field] ?? '');

            if (mb_strlen($address) > self::ADDRESS_MAX) {
                $errors[$field] = __('admin_settings.errors.address_invalid', ['max' => Digits::localize(self::ADDRESS_MAX)]);
            }

            $data[$key->value] = $this->blankToNull($address);
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $data;
    }

    /** The saved value, or the default, so an input without the switch changes nothing. */
    private function currentGuardianApproval(): string
    {
        $row = Setting::query()->where('key', SettingKey::GuardianApproval->value)->first();
        $value = $row !== null ? $row->value : null;

        return $value !== null && $value !== '' ? ($value === '0' ? '0' : '1') : (string) SettingKey::GuardianApproval->default();
    }

    private function phoneIsValid(string $phone): bool
    {
        if (preg_match('/^\+?[0-9][0-9 \-]*[0-9]$/', $phone) !== 1) {
            return false;
        }

        $digits = strlen((string) preg_replace('/\D/', '', $phone));

        return $digits >= 7 && $digits <= 15;
    }

    private function blankToNull(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function text(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }

    /** One line: repeated spaces collapsed, no control characters (the zero width joiners that Bangla needs are kept). Anything that is not text becomes empty. */
    private function line(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        $clean = preg_replace('/\p{Cc}+/u', ' ', $value);

        return trim(preg_replace('/\s+/u', ' ', (string) $clean) ?? '');
    }
}
