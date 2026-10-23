<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells a member that staff suspended the account or lifted the suspension. It holds a name, the kind and
 * the public support email, nothing else. The reason staff wrote is never put in the mail.
 */
class AccountStatusMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public const SUSPENDED = 'suspended';

    public const REACTIVATED = 'reactivated';

    public int $tries = 3;

    public function __construct(
        public readonly string $recipientName,
        public readonly string $kind,
        public readonly ?string $supportEmail,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('account_status.subject_'.$this->kind, ['app' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.account-status');
    }
}
