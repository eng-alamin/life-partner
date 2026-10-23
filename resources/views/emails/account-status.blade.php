<x-mail::message>
# {{ __('account.mail.greeting', ['name' => $recipientName]) }}

{{ __('account_status.body_'.$kind, ['app' => config('app.name')]) }}

@if ($kind === \App\Mail\AccountStatusMail::SUSPENDED)
{{ __('account_status.interests') }}

@if (filled($supportEmail))
{{ __('account_status.contact', ['email' => $supportEmail]) }}
@else
{{ __('account_status.contact_generic') }}
@endif
@endif

{{ config('app.name') }}
</x-mail::message>
