<div>
    <h1 class="text-2xl font-bold text-brand-800">{{ __('admin_users.list.heading') }}</h1>
    <p class="mt-1 text-stone-600">{{ __('admin_users.list.intro') }}</p>

    <x-card class="mt-4" padding="p-4">
        <div class="grid gap-3 sm:grid-cols-3">
            <x-field name="search" :label="__('admin_users.list.search_label')" class="sm:col-span-3">
                <x-input name="search" type="search" maxlength="100" wire:model.live.debounce.400ms="search" :placeholder="__('admin_users.list.search_placeholder')" />
            </x-field>
            <x-field name="status" :label="__('admin_users.list.status_label')">
                <x-select name="status" wire:model.live="status">
                    <option value="all">{{ __('admin_users.list.all') }}</option>
                    <option value="active">{{ __('admin_users.status.active') }}</option>
                    <option value="suspended">{{ __('admin_users.status.suspended') }}</option>
                </x-select>
            </x-field>
            <x-field name="role" :label="__('admin_users.list.role_label')">
                <x-select name="role" wire:model.live="role">
                    <option value="all">{{ __('admin_users.list.all') }}</option>
                    <option value="staff">{{ __('admin_users.list.staff_only') }}</option>
                </x-select>
            </x-field>
        </div>
    </x-card>

    <div class="mt-4">
        @if ($rows === [])
            <x-empty-state :title="__('admin_users.list.empty_title')" :text="__('admin_users.list.empty_text')" />
        @else
            <ul class="space-y-3">
                @foreach ($rows as $row)
                    <li wire:key="user-{{ $row['id'] }}">
                        <x-card variant="small" padding="p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="min-w-0 space-y-1">
                                    <p class="break-words text-lg font-semibold text-stone-900">{{ $row['name'] }}</p>
                                    <p class="break-all text-stone-700" dir="ltr">{{ $row['email'] }}</p>
                                    <p class="flex flex-wrap items-center gap-2 text-sm text-stone-600">
                                        <x-badge :variant="$row['status']->badge()">{{ $row['status']->label() }}</x-badge>
                                        @if ($row['role'])
                                            <x-badge variant="gold">{{ $row['role'] }}</x-badge>
                                        @endif
                                        @unless ($row['verified'])
                                            <x-badge variant="stone">{{ __('admin_users.list.email_unverified') }}</x-badge>
                                        @endunless
                                        <span>{{ __('admin_users.list.biodata') }}: <span class="font-mono" dir="ltr">{{ $row['code'] ?? __('admin_users.list.no_biodata') }}</span></span>
                                        <span>{{ __('admin_users.list.joined', ['date' => $row['joined']]) }}</span>
                                    </p>
                                </div>
                                <x-button :href="route('admin.users.show', $row['id'])" size="sm">{{ __('admin_users.list.open') }}</x-button>
                            </div>
                        </x-card>
                    </li>
                @endforeach
            </ul>

            <div class="mt-4 flex items-center justify-between gap-3">
                <x-button variant="secondary" size="sm" wire:click="previousPage" :disabled="$items->onFirstPage()">{{ __('admin_users.list.previous') }}</x-button>
                <x-button variant="secondary" size="sm" wire:click="nextPage" :disabled="! $items->hasMorePages()">{{ __('admin_users.list.next') }}</x-button>
            </div>
        @endif
    </div>
</div>
