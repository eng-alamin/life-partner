<?php

declare(strict_types=1);

namespace App\Actions\Contact;

use App\Enums\NotificationType;
use App\Models\ContactMessage;
use App\Models\User;
use App\Services\Notification\Notifier;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Saves one message from the public contact form. Guests may write; nobody needs to sign in.
 *
 * - This class is the single place for the rules (what is accepted) and the limits (how often).
 * - The text is cleaned (control characters removed, spaces trimmed) and validated on the server.
 *   A text with more than MAX_LINKS web addresses is refused: that is the typical spam shape.
 * - Limits: MAX_PER_IP_PER_HOUR per address and MAX_PER_EMAIL_PER_DAY per email address. The cache keys
 *   hold only hashes, never the address itself. Only a saved message uses up the limit.
 * - `user_id` comes from the server session (the $user argument). The sender's IP address is kept only as an HMAC.
 * - Nothing is written to the activity log: it would copy personal text.
 * - Staff with contact.handle get an in-app notice (no text of the message in it).
 */
final class SubmitContactMessage
{
    public const MAX_PER_IP_PER_HOUR = 3;

    public const MAX_PER_EMAIL_PER_DAY = 5;

    public const MAX_LINKS = 2;

    public const MESSAGE_MAX = 1000;

    public function __construct(private readonly Notifier $notifier) {}

    /**
     * @param  array<string, mixed>  $input  name, email, phone (optional), subject, message
     */
    public function __invoke(array $input, ?User $user, string $ip): ContactMessage
    {
        $data = $this->validated($input);

        $ipKey = 'contact-ip:'.hash('sha256', $ip);
        $emailKey = 'contact-email:'.hash('sha256', $data['email']);

        $wait = max(
            RateLimiter::tooManyAttempts($ipKey, self::MAX_PER_IP_PER_HOUR) ? RateLimiter::availableIn($ipKey) : 0,
            RateLimiter::tooManyAttempts($emailKey, self::MAX_PER_EMAIL_PER_DAY) ? RateLimiter::availableIn($emailKey) : 0,
        );

        if ($wait > 0) {
            throw ValidationException::withMessages([
                'form' => __('account.throttled_minutes', ['minutes' => (int) ceil($wait / 60)]),
            ]);
        }

        RateLimiter::hit($ipKey, 3600);
        RateLimiter::hit($emailKey, 86400);

        $message = new ContactMessage;
        $message->forceFill([
            'user_id' => $user?->getKey(),
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'subject' => $data['subject'],
            'message' => $data['message'],
            'status' => 'new',
            'ip_hash' => hash_hmac('sha256', $ip, (string) config('app.key')),
        ])->save();

        // Staff with the contact permission are told; the notification holds no personal text.
        $this->notifier->toStaff(NotificationType::StaffContactNew);

        return $message;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{name: string, email: string, phone: ?string, subject: string, message: string}
     */
    private function validated(array $input): array
    {
        $clean = [];

        foreach (['name', 'email', 'phone', 'subject', 'message'] as $field) {
            $value = Arr::get($input, $field);
            $clean[$field] = is_string($value) ? trim($this->withoutControlCharacters($value)) : '';
        }

        $clean['email'] = mb_strtolower($clean['email']);

        $attributes = [];
        foreach (array_keys($clean) as $field) {
            $attributes[$field] = __('contact.fields.'.$field);
        }

        $validated = Validator::make($clean, [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{6,20}$/'],
            'subject' => ['required', 'string', 'min:3', 'max:150'],
            'message' => [
                'required', 'string', 'min:10', 'max:'.self::MESSAGE_MAX,
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (preg_match_all('~(?:https?://|www\.)~i', (string) $value) > self::MAX_LINKS) {
                        $fail(__('contact.errors.links', ['max' => self::MAX_LINKS]));
                    }
                },
            ],
        ], [], $attributes)->validate();

        return [
            'name' => (string) $validated['name'],
            'email' => (string) $validated['email'],
            'phone' => ($validated['phone'] ?? '') === '' ? null : (string) $validated['phone'],
            'subject' => (string) $validated['subject'],
            'message' => (string) $validated['message'],
        ];
    }

    /** Keeps tabs and new lines; removes the other control characters (including null bytes). */
    private function withoutControlCharacters(string $value): string
    {
        return (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
    }
}
