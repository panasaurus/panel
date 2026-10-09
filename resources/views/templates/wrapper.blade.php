<!DOCTYPE html>
@include('blueprint.dashboard.dashboard')
@yield('blueprint.lib')
<!-- Panasaurus — built on Pterodactyl & Pyrodactyl, powered by the Blueprint extension framework -->
<html data-pyro-html lang="en" style="background-color: #000000; height: 100%; width: 100%; margin: 0; padding: 0;">
    <head>
        <title>{{ config('app.name', 'Panel') }}</title>

        @section('meta')
            <meta charset="utf-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
            <meta name="csrf-token" content="{{ csrf_token() }}">
            <meta name="robots" content="noindex">

            <link rel="icon" type="image/png" href="/favicons/favicon-96x96.png" sizes="96x96" />
            <link rel="icon" type="image/svg+xml" href="/favicons/favicon.svg" />
            <link rel="shortcut icon" href="/favicons/favicon.ico" />
            <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png" />
            <meta name="apple-mobile-web-app-title" content="Panasaurus" />
            <link rel="manifest" href="/favicons/site.webmanifest" />

            <meta name="theme-color" content="#000000">
            <meta name="darkreader-lock">
        @show

        @section('user-data')
            @if(!is_null(Auth::user()))
                <script>
                    window.PterodactylUser = {!! json_encode(Auth::user()->toVueObject()) !!};
                </script>
            @endif
            @if(!empty($siteConfiguration))
                <script>
                    window.SiteConfiguration = {!! json_encode($siteConfiguration) !!};
                </script>
            @endif
        @show
        <style>
            @import url('https://fonts.bunny.net/css2?family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap')
        </style>

        @yield('assets')

        @include('layouts.scripts')

        @viteReactRefresh
        @vite('resources/scripts/index.tsx')
    </head>
    <body data-pyro-body class="{{ $css['body'] }}" style="background-color: #000000; height: 100%; width: 100%; margin: 0; padding: 0;">
        @section('content')
            @yield('above-container')
            @yield('container')
            @yield('below-container')
        @show
        @yield('blueprint.wrappers')
    </body>
</html>
