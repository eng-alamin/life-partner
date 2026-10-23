<div>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold text-brand-800">{{ __('admin_identity.review.title') }}</h1>
        <x-button :href="route('admin.verification')" variant="secondary" size="sm">{{ __('admin_identity.review.back') }}</x-button>
    </div>
    <p class="mt-1 text-stone-600">{{ __('admin_identity.review.intro') }}</p>

    @error('form')
        <x-alert type="danger" class="mt-4">{{ $message }}</x-alert>
    @enderror

    <x-card class="mt-4">
        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2">
            <div>
                <dt class="text-sm text-stone-600">{{ __('admin_identity.review.member') }}</dt>
                <dd class="font-mono font-semibold text-stone-900" dir="ltr">{{ $memberCode ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-stone-600">{{ __('admin_identity.review.document') }}</dt>
                <dd class="font-semibold text-stone-900">{{ $verification->document_type->label() }}</dd>
            </div>
            <div>
                <dt class="text-sm text-stone-600">{{ __('admin_identity.review.status') }}</dt>
                <dd><x-badge :variant="$verification->status->badge()">{{ $verification->status->label() }}</x-badge></dd>
            </div>
            <div>
                <dt class="text-sm text-stone-600">{{ __('admin_identity.review.submitted') }}</dt>
                <dd class="font-medium text-stone-900">{{ $verification->submitted_at?->format('Y-m-d H:i') ?? '—' }}</dd>
            </div>
            @if ($verification->reviewed_at)
                <div>
                    <dt class="text-sm text-stone-600">{{ __('admin_identity.review.reviewed') }}</dt>
                    <dd class="font-medium text-stone-900">{{ $verification->reviewed_at->format('Y-m-d H:i') }}</dd>
                </div>
            @endif
            @if ($verification->rejection_reason)
                <div>
                    <dt class="text-sm text-stone-600">{{ __('admin_identity.review.reason') }}</dt>
                    <dd class="font-medium text-stone-900">{{ $verification->rejection_reason->label() }}</dd>
                </div>
            @endif
        </dl>
    </x-card>

    @if ($canDecide)
        <x-card class="mt-4">
            <h2 class="text-lg font-semibold text-stone-900">{{ __('admin_identity.review.pictures') }}</h2>
            <p class="mt-1 text-sm text-stone-600">{{ __('admin_identity.review.pictures_note') }}</p>

            <div class="mt-3 grid gap-4 lg:grid-cols-2">
                @if ($hasFront)
                    <figure>
                        <figcaption class="mb-1 text-sm font-medium text-stone-700">{{ __('admin_identity.review.front') }}</figcaption>
                        <img src="{{ route('admin.verification.file', [$verification->code, 'front']) }}" alt="{{ __('admin_identity.review.front_alt') }}" loading="lazy" class="max-h-[32rem] max-w-full rounded-xl border border-stone-300">
                    </figure>
                @endif
                @if ($hasBack)
                    <figure>
                        <figcaption class="mb-1 text-sm font-medium text-stone-700">{{ __('admin_identity.review.back_side') }}</figcaption>
                        <img src="{{ route('admin.verification.file', [$verification->code, 'back']) }}" alt="{{ __('admin_identity.review.back_alt') }}" loading="lazy" class="max-h-[32rem] max-w-full rounded-xl border border-stone-300">
                    </figure>
                @endif
                @if (! $hasFront && ! $hasBack)
                    <p class="font-medium text-stone-900">{{ __('admin_identity.review.no_pictures') }}</p>
                @endif
            </div>
        </x-card>

        <x-card class="mt-4">
            <h2 class="text-lg font-semibold text-stone-900">{{ __('admin_identity.review.decision') }}</h2>
            <p class="mt-1 text-sm text-stone-600">{{ __('admin_identity.review.decision_note') }}</p>

            <div class="mt-4">
                <x-button wire:click="approve" wire:confirm="{{ __('admin_identity.review.confirm_approve') }}" wire:loading.attr="disabled" wire:target="approve,reject">
                    {{ __('admin_identity.review.approve') }}
                </x-button>
            </div>

            <div class="mt-6 space-y-3 border-t border-stone-200 pt-4">
                <x-field name="reason" :label="__('admin_identity.review.reason_label')" required>
                    <x-select name="reason" wire:model="reason">
                        <option value="">{{ __('admin_identity.review.reason_choose') }}</option>
                        @foreach ($reasons as $item)
                            <option value="{{ $item->value }}">{{ $item->label() }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <x-button variant="danger" wire:click="reject" wire:confirm="{{ __('admin_identity.review.confirm_reject') }}" wire:loading.attr="disabled" wire:target="approve,reject">
                    {{ __('admin_identity.review.reject') }}
                </x-button>
            </div>
        </x-card>
    @else
        <x-alert type="info" class="mt-4">{{ __('admin_identity.review.decided_note') }}</x-alert>
    @endif
</div>
