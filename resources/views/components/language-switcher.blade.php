<form method="POST" action="{{ route('locale.update') }}" {{ $attributes->class(['inline-flex items-center gap-1']) }} aria-label="{{ __('common.language') }}">
    @csrf
    @foreach (config('lifepartner.locales') as $code => $name)
        <button
            type="submit"
            name="locale"
            value="{{ $code }}"
            lang="{{ $code }}"
            @if (app()->getLocale() === $code) aria-current="true" @endif
            class="min-h-11 rounded-lg px-3 text-sm font-medium {{ app()->getLocale() === $code ? 'bg-brand-50 text-brand-800' : 'text-stone-600 hover:bg-white/70' }}"
        >{{ $name }}</button>
    @endforeach
</form>
