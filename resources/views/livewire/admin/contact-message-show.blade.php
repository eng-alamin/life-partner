<div>
    <p><a href="{{ route('admin.contact') }}" class="inline-flex min-h-11 items-center text-brand-700 underline underline-offset-2">{{ __('admin_contact.review.back') }}</a></p>

    <h1 class="mt-2 break-words text-2xl font-bold text-brand-800">{{ $item->subject }}</h1>

    @error('form')
        <x-alert type="danger" class="mt-4">{{ $message }}</x-alert>
    @enderror

    <x-card class="mt-4">
        <dl class="grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-sm text-stone-600">{{ __('admin_contact.review.status') }}</dt>
                <dd class="mt-1"><x-badge :variant="$item->status->badge()">{{ $item->status->label() }}</x-badge></dd>
            </div>
            <div>
                <dt class="text-sm text-stone-600">{{ __('admin_contact.review.sent') }}</dt>
                <dd class="mt-1 text-stone-900">{{ $sentOn }}</dd>
            </div>
            <div>
                <dt class="text-sm text-stone-600">{{ __('admin_contact.review.name') }}</dt>
                <dd class="mt-1 break-words text-stone-900">{{ $item->name }}</dd>
            </div>
            <div>
                <dt class="text-sm text-stone-600">{{ __('admin_contact.review.email') }}</dt>
                <dd class="mt-1 break-all text-stone-900">{{ $item->email }}</dd>
            </div>
            <div>
                <dt class="text-sm text-stone-600">{{ __('admin_contact.review.phone') }}</dt>
                <dd class="mt-1 break-words text-stone-900">{{ $item->phone ?? __('admin_contact.review.no_phone') }}</dd>
            </div>
            <div>
                <dt class="text-sm text-stone-600">{{ __('admin_contact.review.sender') }}</dt>
                <dd class="mt-1 text-stone-900">
                    @if ($item->user_id === null)
                        {{ __('admin_contact.review.guest') }}
                    @elseif ($memberCode !== null)
                        {{ __('admin_contact.review.member_code', ['code' => $memberCode]) }}
                    @else
                        {{ __('admin_contact.review.member') }}
                    @endif
                </dd>
            </div>
        </dl>

        <h2 class="mt-6 text-lg font-semibold text-stone-900">{{ __('admin_contact.review.message') }}</h2>
        <p class="mt-2 whitespace-pre-line break-words text-stone-800">{{ $item->message }}</p>

        @if ($decidedOn !== null)
            <p class="mt-4 text-sm text-stone-600">{{ __('admin_contact.review.decided', ['date' => $decidedOn]) }}</p>
        @endif
    </x-card>

    <x-card class="mt-4">
        <p class="text-stone-700">{{ __('admin_contact.review.reply_help') }}</p>

        <div class="mt-4 flex flex-wrap gap-3">
            <x-button :href="$replyUrl">{{ __('admin_contact.review.reply') }}</x-button>

            @unless ($isHandled)
                <x-button variant="secondary" wire:click="markHandled" wire:loading.attr="disabled" wire:target="markHandled">{{ __('admin_contact.review.mark_handled') }}</x-button>
            @endunless

            @unless ($isSpam)
                <x-button variant="danger" wire:click="markSpam" wire:loading.attr="disabled" wire:target="markSpam">{{ __('admin_contact.review.mark_spam') }}</x-button>
            @endunless

            @unless ($isNew)
                <x-button variant="secondary" wire:click="reopen" wire:loading.attr="disabled" wire:target="reopen">{{ __('admin_contact.review.reopen') }}</x-button>
            @endunless
        </div>
    </x-card>
</div>
