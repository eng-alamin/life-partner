<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Models\Payment;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * A small client for bKash Tokenized Checkout. It only talks to bKash; it never changes a payment.
 *
 * - Money: the amount is built from the payment's integer poisha, never a float.
 * - The access token is cached ENCRYPTED, a little shorter than its life. When bKash answers 401/403
 *   the token is dropped and the call is repeated once with a fresh one. That repeat is safe: bKash
 *   refused the call before doing anything.
 * - Create and Execute are never repeated after a timeout (that could charge twice). A timeout in
 *   Execute raises BkashOutcomeUnknownException so the payment stays pending and is checked later.
 * - Only short codes are logged, never a token, a secret, a phone number or a response body.
 * - The page we send the member to must be on a bKash domain, whatever the response says.
 */
final class BkashClient
{
    private const TOKEN_KEY = 'bkash:id_token';

    public function isEnabled(): bool
    {
        if (! (bool) config('bkash.enabled')) {
            return false;
        }

        foreach (['app_key', 'app_secret', 'username', 'password', 'base_url'] as $key) {
            if (trim((string) config('bkash.'.$key)) === '') {
                return false;
            }
        }

        return true;
    }

    /** "75000" poisha becomes "750.00"; "5" becomes "0.05". Integer math only. */
    public static function taka(int $minor): string
    {
        return intdiv($minor, 100).'.'.str_pad((string) ($minor % 100), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Opens a checkout session at bKash.
     *
     * @return array{payment_id: string, url: string}
     */
    public function createPayment(Payment $payment, string $callbackUrl): array
    {
        $body = $this->post('/checkout/create', [
            'mode' => '0011',
            'payerReference' => $payment->code,
            'callbackURL' => $callbackUrl,
            'amount' => self::taka((int) $payment->amount_minor),
            'currency' => $payment->currency,
            'intent' => 'sale',
            'merchantInvoiceNumber' => $payment->tran_id,
        ], false);

        $code = $this->statusCode($body);
        $paymentId = $body['paymentID'] ?? null;
        $url = $body['bkashURL'] ?? null;

        if ($code !== '0000' || ! is_string($paymentId) || $paymentId === '' || ! is_string($url) || ! $this->isBkashUrl($url)) {
            throw new BkashException('create_failed', $code);
        }

        return ['payment_id' => $paymentId, 'url' => $url];
    }

    /**
     * Finishes a payment after the member came back from bKash. Returns bKash's answer; the caller decides
     * with isCompleted() and its own checks.
     *
     * @return array<string, mixed>
     *
     * @throws BkashOutcomeUnknownException when the outcome is not known
     */
    public function executePayment(string $paymentId): array
    {
        return $this->post('/checkout/execute', ['paymentID' => $paymentId], true);
    }

    /**
     * Asks bKash for the state of a payment (used when Execute was unclear).
     *
     * @return array<string, mixed>
     */
    public function queryPayment(string $paymentId): array
    {
        return $this->post('/checkout/payment/status', ['paymentID' => $paymentId], false);
    }

    /**
     * True only for a finished, successful payment: statusCode "0000" and transactionStatus "Completed".
     * A Create answer with "0000" is NOT a payment. Amount, currency and invoice are checked by the caller.
     *
     * @param  array<string, mixed>  $response
     */
    public static function isCompleted(array $response): bool
    {
        return ($response['statusCode'] ?? null) === '0000'
            && ($response['transactionStatus'] ?? null) === 'Completed';
    }

    // ---------------------------------------------------------------- internals

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function post(string $path, array $body, bool $unknownOnFailure): array
    {
        $response = null;

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $token = $this->idToken($attempt === 2);

            try {
                $response = Http::connectTimeout(5)
                    ->timeout(max(2, (int) config('bkash.timeout')))
                    ->withHeaders(['authorization' => $token, 'x-app-key' => (string) config('bkash.app_key')])
                    ->acceptJson()
                    ->asJson()
                    ->post($this->url($path), $body);
            } catch (ConnectionException) {
                // Nothing from the exception is kept: its text can contain the address.
                $this->log($path, null, null);

                throw $unknownOnFailure ? new BkashOutcomeUnknownException('network') : new BkashException('network');
            }

            if ($attempt === 1 && in_array($response->status(), [401, 403], true)) {
                Cache::forget(self::TOKEN_KEY);

                continue;
            }

            break;
        }

        return $this->decode($response, $path, $unknownOnFailure);
    }

    /** @return array<string, mixed> */
    private function decode(?Response $response, string $path, bool $unknownOnFailure): array
    {
        if ($response === null) {
            throw new BkashException('network');
        }

        // Refused even with a fresh token: nothing was processed, so this is a plain failure.
        if (in_array($response->status(), [401, 403], true)) {
            $this->log($path, $response->status(), null);

            throw new BkashException('auth');
        }

        $json = $response->json();

        if ($response->serverError()) {
            $this->log($path, $response->status(), null);

            throw $unknownOnFailure ? new BkashOutcomeUnknownException('server_error') : new BkashException('server_error');
        }

        if (! is_array($json)) {
            $this->log($path, $response->status(), null);

            throw $unknownOnFailure ? new BkashOutcomeUnknownException('bad_response') : new BkashException('bad_response');
        }

        $this->log($path, $response->status(), $this->statusCode($json));

        return $json;
    }

    private function idToken(bool $forceNew): string
    {
        if (! $forceNew) {
            $cached = Cache::get(self::TOKEN_KEY);

            if (is_string($cached)) {
                try {
                    return Crypt::decryptString($cached);
                } catch (DecryptException) {
                    Cache::forget(self::TOKEN_KEY);
                }
            }
        }

        return $this->grantToken();
    }

    private function grantToken(): string
    {
        try {
            $response = Http::connectTimeout(5)
                ->timeout(max(2, (int) config('bkash.timeout')))
                ->withHeaders([
                    'username' => (string) config('bkash.username'),
                    'password' => (string) config('bkash.password'),
                ])
                ->acceptJson()
                ->asJson()
                ->post($this->url('/checkout/token/grant'), [
                    'app_key' => (string) config('bkash.app_key'),
                    'app_secret' => (string) config('bkash.app_secret'),
                ]);
        } catch (ConnectionException) {
            $this->log('/checkout/token/grant', null, null);

            throw new BkashException('network');
        }

        $json = $response->json();
        $code = is_array($json) ? $this->statusCode($json) : null;
        $token = is_array($json) ? ($json['id_token'] ?? null) : null;

        $this->log('/checkout/token/grant', $response->status(), $code);

        if (! $response->successful() || $code !== '0000' || ! is_string($token) || $token === '') {
            throw new BkashException('auth', $code);
        }

        $expires = is_array($json) && is_numeric($json['expires_in'] ?? null) ? (int) $json['expires_in'] : 3600;

        // Keep it a little shorter than its life, and never less than a minute.
        Cache::put(self::TOKEN_KEY, Crypt::encryptString($token), max(60, min($expires, 3600) - 120));

        return $token;
    }

    private function url(string $path): string
    {
        return rtrim((string) config('bkash.base_url'), '/').$path;
    }

    /** @param  array<string, mixed>  $json */
    private function statusCode(array $json): ?string
    {
        $code = $json['statusCode'] ?? null;

        return is_scalar($code) ? (string) $code : null;
    }

    /** The member is only ever sent to https on a bKash domain. */
    private function isBkashUrl(string $url): bool
    {
        $parts = parse_url($url);

        if (! is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || ! isset($parts['host'])) {
            return false;
        }

        $host = strtolower($parts['host']);

        foreach (['bkash.com', 'bka.sh'] as $domain) {
            if ($host === $domain || str_ends_with($host, '.'.$domain)) {
                return true;
            }
        }

        return false;
    }

    private function log(string $path, ?int $http, ?string $code): void
    {
        Log::info('bkash.call', ['path' => $path, 'http' => $http, 'status_code' => $code]);
    }
}
