<?php

declare(strict_types=1);

namespace App\Livewire\Account;

use App\Actions\Identity\SubmitIdentityVerification;
use App\Enums\IdentityDocumentType;
use App\Enums\IdentityStatus;
use App\Models\Biodata;
use App\Models\IdentityVerification;
use App\Support\Digits;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * The member's own "verify my identity" page. It only ever works on the signed-in member.
 * Every input is untrusted: the action checks everything again on the server.
 */
#[Layout('layouts::app')]
class IdentityVerificationPage extends Component
{
    use WithFileUploads;

    public string $documentType = 'nid';

    /** The chosen pictures (Livewire temporary uploads). */
    public $front = null;

    public $back = null;

    public function submit(SubmitIdentityVerification $submit): void
    {
        $this->resetErrorBag();
        $this->throttle();

        $maxKb = (int) config('lifepartner.identity.max_kb');

        $messages = [
            'front.required' => __('identity.errors.front_required'),
            'front.file' => __('identity.errors.unreadable'),
            'front.uploaded' => __('identity.errors.upload_failed'),
            'front.max' => __('identity.errors.too_big'),
            'front.mimes' => __('identity.errors.type'),
            'back.file' => __('identity.errors.unreadable'),
            'back.uploaded' => __('identity.errors.upload_failed'),
            'back.max' => __('identity.errors.too_big'),
            'back.mimes' => __('identity.errors.type'),
        ];

        $this->validate([
            'front' => ['required', 'file', 'max:'.$maxKb, 'mimes:jpg,jpeg,png,webp'],
            'back' => ['nullable', 'file', 'max:'.$maxKb, 'mimes:jpg,jpeg,png,webp'],
        ], $messages);

        $submit(auth()->user(), $this->documentType, $this->front, $this->back);

        $this->reset(['front', 'back']);

        session()->flash('status', __('identity.flash.submitted'));
        $this->redirectRoute('account.verification');
    }

    public function render()
    {
        $userId = auth()->id();

        $biodata = Biodata::query()->where('user_id', $userId)->first(['id', 'user_id', 'identity_verified_at']);
        $latest = IdentityVerification::query()
            ->where('user_id', $userId)
            ->latest('id')
            ->first(['id', 'code', 'user_id', 'status', 'document_type', 'rejection_reason', 'submitted_at', 'reviewed_at']);

        $verified = $biodata?->isIdentityVerified() ?? false;
        $pending = ! $verified && $latest?->status === IdentityStatus::Pending;
        $rejected = ! $verified && ! $pending && $latest?->status === IdentityStatus::Rejected;

        $maxMb = max(1, intdiv((int) config('lifepartner.identity.max_kb'), 1024));

        return view('livewire.account.identity-verification', [
            'hasBiodata' => $biodata !== null,
            'verified' => $verified,
            'pending' => $pending,
            'rejected' => $rejected,
            'latest' => $latest,
            'submittedOn' => $latest !== null ? Digits::localize($latest->submitted_at->translatedFormat('j M Y')) : null,
            'types' => IdentityDocumentType::cases(),
            'maxMb' => Digits::localize($maxMb),
            'expireDays' => Digits::localize((int) config('lifepartner.identity.expire_days')),
            'canSubmit' => $biodata !== null && ! $verified && ! $pending && ! auth()->user()->hasDeletionRequested(),
        ])->title(__('identity.title'));
    }

    /** Limits attempts per hour per member. */
    private function throttle(): void
    {
        $key = 'identity-submit:'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            throw ValidationException::withMessages([
                'form' => __('account.throttled_minutes', ['minutes' => max(1, (int) ceil(RateLimiter::availableIn($key) / 60))]),
            ]);
        }

        RateLimiter::hit($key, 3600);
    }
}
