<?php

declare(strict_types=1);

namespace App\Services\Sms;

use App\Support\SmsNumber;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Provider: sms.net.bd (Alpha SMS). POST {base}/sendsms with a JSON body
 * {api_key, msg, to, sender_id?}. The answer is {"error": 0, "msg": "...",
 * "data": {"request_id": ...}}; error 0 means accepted.
 *
 * The API key travels in the request body only, never in the URL, and it is never
 * written to a log. Provider error codes (400, 413, 415, 416, 417 ...) are final,
 * except 409 (unknown server error), which is worth a retry.
 */
final class SmsNetBdGateway implements SmsGateway
{
    /** Provider codes that may succeed if tried again later. */
    private const RETRYABLE_CODES = [409];

    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly ?string $senderId,
        private readonly int $timeoutSeconds,
    ) {}

    public function isReady(): bool
    {
        return $this->apiKey !== '' && $this->baseUrl !== '';
    }

    public function send(string $localNumber, string $message): SmsResult
    {
        if (! $this->isReady()) {
            return SmsResult::failed(SmsResult::NOT_CONFIGURED, false);
        }

        $payload = [
            'api_key' => $this->apiKey,
            'msg' => $message,
            'to' => SmsNumber::international($localNumber),
        ];

        if ($this->senderId !== null && $this->senderId !== '') {
            $payload['sender_id'] = $this->senderId;
        }

        try {
            $response = Http::connectTimeout(5)
                ->timeout(max(2, $this->timeoutSeconds))
                ->acceptJson()
                ->asJson()
                ->post(rtrim($this->baseUrl, '/').'/sendsms', $payload);
        } catch (ConnectionException) {
            // Nothing from the exception is kept: its text can contain the address.
            return SmsResult::failed(SmsResult::NETWORK, true);
        }

        if ($response->serverError()) {
            return SmsResult::failed(SmsResult::BAD_RESPONSE, true);
        }

        $body = $response->json();

        if (! is_array($body) || ! array_key_exists('error', $body) || ! is_numeric($body['error'])) {
            return SmsResult::failed(SmsResult::BAD_RESPONSE, false);
        }

        // The provider's own docs show error as a number, but one example compares
        // it as text, so both forms are accepted.
        $code = (int) $body['error'];

        if ($code === 0) {
            $requestId = $body['data']['request_id'] ?? null;

            return SmsResult::sent(is_scalar($requestId) ? (string) $requestId : null);
        }

        return SmsResult::failed($code, in_array($code, self::RETRYABLE_CODES, true));
    }
}
