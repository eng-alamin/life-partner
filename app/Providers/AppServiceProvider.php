<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Otp\EmailOtpChannel;
use App\Services\Otp\OtpChannel;
use App\Services\Sms\LogSmsGateway;
use App\Services\Sms\NullSmsGateway;
use App\Services\Sms\SmsGateway;
use App\Services\Sms\SmsNetBdGateway;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Email codes are the default. Phone verification builds its own OtpService
        // with SmsOtpChannel (see App\Actions\Phone).
        $this->app->bind(OtpChannel::class, EmailOtpChannel::class);

        // The SMS provider is chosen by LP_SMS_DRIVER: smsnetbd, log or null.
        $this->app->singleton(SmsGateway::class, static function (): SmsGateway {
            return match ((string) config('lifepartner.sms.driver')) {
                'smsnetbd' => new SmsNetBdGateway(
                    (string) config('lifepartner.sms.api_key'),
                    (string) config('lifepartner.sms.base_url'),
                    config('lifepartner.sms.sender_id') ?: null,
                    (int) config('lifepartner.sms.timeout_seconds'),
                ),
                'log' => new LogSmsGateway,
                default => new NullSmsGateway,
            };
        });
    }

    public function boot(): void
    {
        Blade::anonymousComponentPath(resource_path('views/layouts'), 'layouts');

        Model::preventLazyLoading(! $this->app->isProduction());

        // Minimum 8 characters; in production also reject passwords found in
        // known data breaches (uses the Have I Been Pwned range API).
        Password::defaults(fn (): Password => Password::min(8)
            ->when($this->app->isProduction(), fn (Password $rule) => $rule->uncompromised()));
    }
}
