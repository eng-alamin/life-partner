<div>
    <h1 class="text-2xl font-bold text-brand-800">{{ __('interests.ui.inbox_heading') }}</h1>
    <p class="mt-1 text-stone-700">{{ __('interests.ui.inbox_intro') }}</p>

    @if (! $hasBiodata)
        <x-empty-state class="mt-4" :title="__('interests.ui.no_biodata_title')" :text="__('interests.ui.no_biodata_text')">
            <x-button :href="route('my-biodata')" size="lg">{{ __('interests.ui.create_biodata') }}</x-button>
        </x-empty-state>
    @else
        <div class="mt-4 flex gap-2" role="tablist" aria-label="{{ __('interests.ui.inbox_heading') }}">
            @foreach (['received', 'sent'] as $name)
                <button
                    type="button"
                    role="tab"
                    wire:click="showTab('{{ $name }}')"
                    aria-selected="{{ $tab === $name ? 'true' : 'false' }}"
                    @class([
                        'min-h-11 flex-1 rounded-xl px-4 py-2 font-semibold transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 sm:flex-none',
                        'bg-brand-700 text-white' => $tab === $name,
                        'border border-stone-300 bg-white/80 text-stone-800 hover:bg-white' => $tab !== $name,
                    ])
                >
                    {{ __('interests.ui.tab_'.$name) }}
                    @if ($counts[$name] > 0)
                        <span class="ms-1 rounded-full bg-gold-50 px-2 py-0.5 text-sm text-gold-700">{{ \App\Support\Digits::localize($counts[$name]) }}</span>
                    @endif
                </button>
            @endforeach
        </div>

        @if ($notice !== '')
            <x-alert type="success" class="mt-4">{{ $notice }}</x-alert>
        @endif

        @error('form')
            <x-alert type="danger" class="mt-4">
                {{ $message }}
                @if ($message === __('billing.errors.no_credit'))
                    <a href="{{ route('plans.index') }}" class="mt-2 block font-semibold underline">{{ __('credits.buy_link') }}</a>
                @endif
            </x-alert>
        @enderror

        @if ($rows === [])
            <x-empty-state class="mt-4" :title="__('interests.ui.empty_'.$tab.'_title')" :text="__('interests.ui.empty_'.$tab.'_text')">
                @if ($tab === 'sent')
                    <x-button :href="route('biodatas.index')" size="sm">{{ __('interests.ui.find_matches') }}</x-button>
                @endif
            </x-empty-state>
        @else
            <ul class="mt-4 space-y-4">
                @foreach ($rows as $row)
                    <li wire:key="interest-{{ $row['code'] }}">
                        <x-card variant="small" padding="p-4 sm:p-5">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-badge :variant="$row['status']->badge()">{{ $row['status']->label() }}</x-badge>
                                @if ($row['status'] === \App\Enums\InterestStatus::Accepted && $row['guardian_status'] !== \App\Enums\GuardianApprovalStatus::NotRequired)
                                    <x-badge :variant="$row['guardian_status']->badge()">{{ __('interests.ui.guardian.'.$row['guardian_status']->value) }}</x-badge>
                                @endif
                                <span class="text-sm text-stone-600">{{ __('interests.ui.sent_on', ['date' => $row['sent_on']]) }}</span>
                                @if ($row['expires_on'])
                                    <span class="text-sm text-stone-600">· {{ __('interests.ui.expires_on', ['date' => $row['expires_on']]) }}</span>
                                @endif
                            </div>

                            @if ($row['card'] === null)
                                <p class="mt-3 text-stone-700">{{ __('interests.ui.unavailable') }}</p>
                            @else
                                <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                                    <p class="font-mono text-lg font-semibold text-stone-900">{{ $row['card']['code'] }}</p>
                                    <x-badge variant="brand">{{ $row['card']['type'] }}</x-badge>
                                </div>

                                <p class="mt-1 text-stone-700">
                                    @if ($row['card']['age'] !== null)
                                        {{ __('search.card.age', ['age' => \App\Support\Digits::localize($row['card']['age'])]) }}@if ($row['card']['marital_status']) · {{ $row['card']['marital_status'] }}@endif
                                    @endif
                                    @if ($row['card']['location'] !== '')
                                        · {{ $row['card']['location'] }}
                                    @endif
                                    @if (filled($row['card']['profession']))
                                        · {{ $row['card']['profession'] }}
                                    @endif
                                </p>
                            @endif

                            @if (filled($row['message']))
                                <p class="mt-3 text-sm font-medium text-stone-600">{{ __('interests.ui.message_heading') }}</p>
                                <p class="whitespace-pre-line text-stone-900">{{ $row['message'] }}</p>
                            @endif

                            <div class="mt-4 flex flex-wrap gap-2">
                                @if ($row['profile_url'])
                                    <x-button :href="$row['profile_url']" variant="secondary" size="sm">{{ __('interests.ui.view_profile') }}</x-button>
                                @endif

                                @if ($row['can_respond'])
                                    <x-button size="sm" wire:click="accept('{{ $row['code'] }}')" wire:loading.attr="disabled">{{ __('interests.ui.accept') }}</x-button>
                                    <x-button variant="secondary" size="sm" wire:click="decline('{{ $row['code'] }}')" wire:confirm="{{ __('interests.ui.confirm_decline') }}" wire:loading.attr="disabled">{{ __('interests.ui.decline') }}</x-button>
                                @endif

                                @if ($row['can_create_link'])
                                    <x-button size="sm" wire:click="createGuardianLink('{{ $row['code'] }}')" wire:loading.attr="disabled">
                                        {{ $row['link_valid_until'] ? __('guardian.inbox.new_link') : __('guardian.inbox.create_link') }}
                                    </x-button>
                                @endif

                                @if ($row['can_reveal'])
                                    <x-button size="sm" wire:click="revealContact('{{ $row['code'] }}')" wire:loading.attr="disabled">{{ __('contact.inbox.show') }}</x-button>
                                @endif

                                @if ($row['can_cancel'])
                                    <x-button variant="secondary" size="sm" wire:click="cancel('{{ $row['code'] }}')" wire:confirm="{{ __('interests.ui.confirm_cancel') }}" wire:loading.attr="disabled">{{ __('interests.ui.cancel') }}</x-button>
                                @endif
                            </div>

                            @if ($row['can_create_link'])
                                <p class="mt-3 text-sm text-stone-600">
                                    {{ $row['link_valid_until'] ? __('guardian.inbox.link_valid_hint').' '.__('guardian.inbox.valid_until', ['date' => $row['link_valid_until']]) : __('guardian.inbox.waiting_hint') }}
                                </p>
                            @endif

                            @if ($row['contact'] !== null)
                                <div class="mt-3 rounded-xl border border-brand-100 bg-brand-50/60 p-3">
                                    <p class="text-sm font-medium text-brand-900">{{ __('contact.inbox.heading') }}</p>
                                    <dl class="mt-2 space-y-1 text-stone-900">
                                        <div class="flex flex-wrap gap-x-2"><dt class="text-stone-600">{{ __('contact.inbox.name') }}:</dt><dd class="font-semibold">{{ $row['contact']['name'] }}</dd></div>
                                        @if ($row['contact']['relation'] !== '')
                                            <div class="flex flex-wrap gap-x-2"><dt class="text-stone-600">{{ __('contact.inbox.relation') }}:</dt><dd>{{ $row['contact']['relation'] }}</dd></div>
                                        @endif
                                        <div class="flex flex-wrap gap-x-2"><dt class="text-stone-600">{{ __('contact.inbox.phone') }}:</dt><dd><a class="font-semibold text-brand-800 underline" href="tel:{{ preg_replace('/[^\d+]/', '', $row['contact']['phone']) }}">{{ $row['contact']['phone'] }}</a></dd></div>
                                    </dl>
                                    <p class="mt-2 text-sm text-stone-600">{{ __('contact.inbox.note') }}</p>
                                    <x-button class="mt-2" variant="secondary" size="sm" wire:click="closeContact">{{ __('contact.inbox.hide') }}</x-button>
                                </div>
                            @endif

                            @if ($guardianLink !== '' && $guardianLinkCode === $row['code'])
                                <div class="mt-3 rounded-xl border border-brand-100 bg-brand-50/60 p-3" x-data="{ copied: false }">
                                    <p class="text-sm font-medium text-brand-900">{{ __('guardian.inbox.link_ready') }}</p>
                                    <label for="guardian-link-{{ $row['code'] }}" class="mt-2 block text-sm text-stone-700">{{ __('guardian.inbox.link_label') }}</label>
                                    <input
                                        id="guardian-link-{{ $row['code'] }}"
                                        type="text"
                                        readonly
                                        x-ref="link"
                                        value="{{ $guardianLink }}"
                                        class="mt-1 w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm text-stone-900 focus-visible:outline-2 focus-visible:outline-brand-600"
                                        onfocus="this.select()"
                                    >
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <x-button size="sm" x-on:click="navigator.clipboard.writeText($refs.link.value).then(() => copied = true)">
                                            <span x-show="! copied">{{ __('guardian.inbox.copy') }}</span>
                                            <span x-show="copied" x-cloak>{{ __('guardian.inbox.copied') }}</span>
                                        </x-button>
                                        <x-button variant="secondary" size="sm" wire:click="closeLink">{{ __('guardian.inbox.close') }}</x-button>
                                    </div>
                                </div>
                            @endif
                        </x-card>
                    </li>
                @endforeach
            </ul>

            @if ($hasMore)
                <div class="mt-4 flex justify-center">
                    <x-button variant="secondary" wire:click="loadMore" wire:loading.attr="disabled">{{ __('interests.ui.load_more') }}</x-button>
                </div>
            @endif
        @endif
    @endif
</div>
