@props(['title' => null, 'description' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('layouts.partials.head', ['noindex' => true])
</head>
<body class="min-h-dvh">
    <x-panel.shell panel="member" :title="$title">{{ $slot }}</x-panel.shell>
</body>
</html>
