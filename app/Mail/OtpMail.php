<?php

declare(strict_types=1);

namespace App\Mail;

use App\Enums\OtpPurpose;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly string $recipientName,
        public readonly string $code,
        public readonly OtpPurpose $purpose,
        public readonly int $ttlMinutes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('account.mail.subject_'.$this->purpose->value, ['app' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.otp');
    }
}
