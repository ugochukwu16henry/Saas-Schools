<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'RiseFlow') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('global_assets/images/favicon.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('global_assets/images/favicon.png') }}">

    @include('partials.login.inc_top')
</head>

<body>
@include('partials.login.header')
@yield('content')
@include('partials.login.footer')

{{-- Floating dark/light mode toggle for auth pages --}}
<button id="theme-toggle" class="theme-toggle-btn" title="Toggle dark/light mode" aria-label="Toggle dark/light mode"
    style="position:fixed;bottom:20px;right:20px;z-index:9999;width:42px;height:42px;border-radius:50%;box-shadow:0 4px 12px rgba(0,0,0,0.2);">
    <span id="theme-icon">🌙</span>
</button>

</body>

</html>
