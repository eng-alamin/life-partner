<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Actions\Settings\SaveSettings;
use App\Enums\SettingKey;
use App\Models\Setting;
use App\Services\Settings\SiteSettings;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Edits the few safe site settings. Every value is validated again by SaveSettings, and the
 * permission is checked again on every save (a form that stayed open after the role was
 * taken away can not save).
 */
#[Layout('layouts::admin')]
class SettingsForm extends Component
{
    public bool $registration_open = true;

    public bool $guardian_approval = true;

    public string $contact_email = '';

    public string $contact_phone = '';

    public string $contact_address_bn = '';

    public string $contact_address_en = '';

    public function mount(SiteSettings $settings): void
    {
        Gate::authorize('viewAny', Setting::class);

        $this->registration_open = $settings->registrationOpen();
        $this->guardian_approval = $settings->guardianApprovalRequired();
        $this->contact_email = $settings->get(SettingKey::ContactEmail) ?? '';
        $this->contact_phone = $settings->get(SettingKey::ContactPhone) ?? '';
        $this->contact_address_bn = $settings->get(SettingKey::ContactAddressBn) ?? '';
        $this->contact_address_en = $settings->get(SettingKey::ContactAddressEn) ?? '';
    }

    public function save(SaveSettings $save): void
    {
        Gate::authorize('viewAny', Setting::class);

        $this->resetErrorBag();
        $this->throttle();

        $save(auth()->user(), $this->only([
            'registration_open',
            'guardian_approval',
            'contact_email',
            'contact_phone',
            'contact_address_bn',
            'contact_address_en',
        ]));

        session()->flash('status', __('admin_settings.flash.saved'));
        $this->redirectRoute('admin.settings');
    }

    public function render()
    {
        return view('livewire.admin.settings-form', [
            'registrationIsOpen' => app(SiteSettings::class)->registrationOpen(),
            'addressMax' => SaveSettings::ADDRESS_MAX,
        ])->title(__('admin_settings.title'));
    }

    private function throttle(): void
    {
        $key = 'settings-admin:'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, 60)) {
            throw ValidationException::withMessages([
                'form' => __('account.throttled_minutes', ['minutes' => max(1, (int) ceil(RateLimiter::availableIn($key) / 60))]),
            ]);
        }

        RateLimiter::hit($key, 3600);
    }
}
