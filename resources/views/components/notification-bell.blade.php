{{-- Bell with the unread count. One small query per page load; the count is not live. --}}
@if (auth()->check() && Route::has('notifications.index'))
    @php
        $unread = (int) auth()->user()->unreadNotifications()->count();
        $label = __('nav.notifications').($unread > 0 ? ' ('.\App\Support\Digits::localize($unread).')' : '');
    @endphp

    <a href="{{ route('notifications.index') }}" {{ $attributes->class(['relative inline-flex size-11 items-center justify-center rounded-lg text-stone-700 hover:bg-white/70 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600']) }} aria-label="{{ $label }}">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>
        @if ($unread > 0)
            <span class="absolute -end-0.5 -top-0.5 min-w-5 rounded-full bg-red-700 px-1 text-center text-xs font-bold leading-5 text-white" aria-hidden="true">{{ $unread > 99 ? '99+' : \App\Support\Digits::localize($unread) }}</span>
        @endif
    </a>
@endif
