<?php

declare(strict_types=1);

namespace App\Livewire\Contact;

use App\Actions\Contact\SubmitContactMessage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The public contact form, shown on the contact page. Guests may use it.
 *
 * Every public property is untrusted input. The rules and the limits live in SubmitContactMessage.
 * Two quiet bot checks are added here: a hidden "website" field that people never fill (a bot that
 * fills it gets the normal "sent" screen and nothing is saved), and a minimum time between opening
 * the page and sending (the start time is #[Locked], so the browser cannot change it).
 */
class ContactForm extends Component
{
    private const MIN_SECONDS = 3;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $subject = '';

    public string $message = '';

    /** Honeypot. A real person never sees or fills this field. */
    public string $website = '';

    #[Locked]
    public int $startedAt = 0;

    public bool $sent = false;

    public function mount(): void
    {
        $user = auth()->user();

        if ($user !== null) {
            $this->name = (string) $user->name;
            $this->email = (string) $user->email;
        }

        $this->startedAt = now()->getTimestamp();
    }

    public function send(SubmitContactMessage $submit): void
    {
        // A bot filled the hidden field: show the normal result, save nothing.
        if ($this->website !== '') {
            $this->finish();

            return;
        }

        if (now()->getTimestamp() - $this->startedAt < self::MIN_SECONDS) {
            throw ValidationException::withMessages(['form' => __('contact.errors.too_fast')]);
        }

        $submit(
            [
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'subject' => $this->subject,
                'message' => $this->message,
            ],
            auth()->user(),
            (string) request()->ip(),
        );

        $this->finish();
    }

    public function render()
    {
        return view('livewire.contact.contact-form');
    }

    private function finish(): void
    {
        $this->reset(['phone', 'subject', 'message', 'website']);
        $this->resetErrorBag();
        $this->sent = true;
    }
}
