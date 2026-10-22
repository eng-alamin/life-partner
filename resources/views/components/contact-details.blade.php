{{--
    The public contact details staff saved in Admin > Settings.
    Shows nothing when no detail is set.
    compact: a small list for the footer. Otherwise a card with a heading (contact page).
    Every value is printed escaped.
--}}
@props(['compact' => false])

@php
    $site = app(\App\Services\Settings\SiteSettings::class);
    $email = $site->contactEmail();
    $phone = $site->contactPhone();
    $address = $site->contactAddress();
@endphp

@if ($email !== null || $phone !== null || $address !== null)
    @if ($compact)
        <ul {{ $attributes->class(['space-y-1 text-stone-600']) }}>
            @if ($email !== null)
                <li><a href="mailto:{{ $email }}" class="hover:text-brand-700">{{ $email }}</a></li>
            @endif
            @if ($phone !== null)
                <li><a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}" class="hover:text-brand-700">{{ \App\Support\Digits::localize($phone) }}</a></li>
            @endif
            @if ($address !== null)
                <li>{{ $address }}</li>
            @endif
        </ul>
    @else
        <div {{ $attributes }}>
            <x-card>
                <h2 class="text-xl font-bold text-brand-800">{{ __('site.contact.heading') }}</h2>
                <dl class="mt-3 space-y-2">
                    @if ($email !== null)
                        <div class="flex flex-wrap gap-x-2"><dt class="text-stone-600">{{ __('site.contact.email') }}:</dt><dd><a class="font-semibold text-brand-800 underline" href="mailto:{{ $email }}">{{ $email }}</a></dd></div>
                    @endif
                    @if ($phone !== null)
                        <div class="flex flex-wrap gap-x-2"><dt class="text-stone-600">{{ __('site.contact.phone') }}:</dt><dd><a class="font-semibold text-brand-800 underline" href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}">{{ \App\Support\Digits::localize($phone) }}</a></dd></div>
                    @endif
                    @if ($address !== null)
                        <div class="flex flex-wrap gap-x-2"><dt class="text-stone-600">{{ __('site.contact.address') }}:</dt><dd>{{ $address }}</dd></div>
                    @endif
                </dl>
            </x-card>
        </div>
    @endif
@endif
