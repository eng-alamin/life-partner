<x-mail::message>
# {{ __('account.mail.greeting', ['name' => $recipientName]) }}

{{ __('account.mail.intro_'.$purpose->value, ['app' => config('app.name')]) }}

<x-mail::panel>
{{ $code }}
</x-mail::panel>

{{ __('account.mail.expires', ['minutes' => $ttlMinutes]) }}

{{ __('account.mail.ignore') }}

{{ config('app.name') }}
</x-mail::message>
