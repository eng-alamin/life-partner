<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Actions\Contact\HandleContactMessage;
use App\Enums\ContactMessageStatus;
use App\Models\Biodata;
use App\Models\ContactMessage;
use App\Support\Digits;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * One contact message. The message id is locked (the browser cannot change it); every
 * change is checked again on the server inside HandleContactMessage. Staff reply from
 * their own email program through the "reply" link: nothing is sent from this page.
 */
#[Layout('layouts::admin')]
class ContactMessageShow extends Component
{
    #[Locked]
    public int $messageId = 0;

    public function mount(ContactMessage $message): void
    {
        Gate::authorize('handle', $message);

        $this->messageId = (int) $message->getKey();
    }

    public function markHandled(HandleContactMessage $handler): void
    {
        $this->begin();

        $handler->markHandled(auth()->user(), $this->messageId);

        $this->finish('handled');
    }

    public function markSpam(HandleContactMessage $handler): void
    {
        $this->begin();

        $handler->markSpam(auth()->user(), $this->messageId);

        $this->finish('spam');
    }

    public function reopen(HandleContactMessage $handler): void
    {
        $this->begin();

        $handler->reopen(auth()->user(), $this->messageId);

        $this->finish('reopened');
    }

    public function render()
    {
        $message = ContactMessage::query()->findOrFail($this->messageId);

        Gate::authorize('handle', $message);

        // A query instead of a relation: lazy loading is switched off outside production.
        $memberCode = $message->user_id !== null
            ? Biodata::withTrashed()->where('user_id', $message->user_id)->value('public_code')
            : null;

        return view('livewire.admin.contact-message-show', [
            'item' => $message,
            'isNew' => $message->status === ContactMessageStatus::New,
            'isHandled' => $message->status === ContactMessageStatus::Handled,
            'isSpam' => $message->status === ContactMessageStatus::Spam,
            'memberCode' => $memberCode,
            'replyUrl' => 'mailto:'.$message->email.'?subject='.rawurlencode('Re: '.$message->subject),
            'sentOn' => Digits::localize($message->created_at->translatedFormat('j M Y, g:i a')),
            'decidedOn' => $message->handled_at !== null
                ? Digits::localize($message->handled_at->translatedFormat('j M Y'))
                : null,
        ])->title(__('admin_contact.review.title'));
    }

    private function begin(): void
    {
        $this->resetErrorBag();

        $key = 'contact-review:'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, 60)) {
            throw ValidationException::withMessages([
                'form' => __('account.throttled_minutes', ['minutes' => max(1, (int) ceil(RateLimiter::availableIn($key) / 60))]),
            ]);
        }

        RateLimiter::hit($key, 3600);
    }

    private function finish(string $outcome): void
    {
        session()->flash('status', __('admin_contact.flash.'.$outcome));
        $this->redirectRoute('admin.contact');
    }
}
