<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
    </head>
    <body class="font-sans text-zinc-900 antialiased">
        

        <div class="min-h-screen bg-zinc-100 print:bg-white">
            @livewire('navigation-menu')
            
            <x-banner />

            {{-- Page Heading --}}
            @if (isset($header))
                <header class="">
                    <div {{ $header->attributes->class(['max-w-7xl','mx-auto','py-6','px-4','sm:px-6','lg:px-8']) }}>
                        {{ $header }}
                    </div>
                </header>
            @endif

            <x-broadcast-communications />

            {{-- Page Content --}}
            <main>
                {{ $slot }}
            </main>

            <footer class="mt-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="text-sm text-zinc-600">
                        <p class="font-bold">{{ __('Organizer') }}</p>
                        @if (config('races.organizer.url'))
                            <p><a href="{{ config('races.organizer.url') }}" target="_blank" rel="noopener">{{ config('races.organizer.name') }}</a></p>
                        @else
                            <p>{{ config('races.organizer.name') }}</p>
                        @endif
                        <p>{{ config('races.organizer.address') }}</p>
                    </div>
                    <div class="text-sm text-zinc-600 print:hidden">
                        <p class="font-bold">{{ __('Change language') }}</p>
                        <p><a href="{{ route('language.change', ['lang' => 'it']) }}" class="underline text-sm text-zinc-600 hover:text-zinc-900">{{ __('Italiano') }}</a></p>
                        <p><a href="{{ route('language.change', ['lang' => 'en']) }}" class="underline text-sm text-zinc-600 hover:text-zinc-900">{{ __('English') }}</a></p>
                    </div>
                </div>
                <div class="mt-4 prose prose-sm print:hidden">
                    <a href="{{ route('policy.show') }}" class="underline text-sm text-zinc-600 hover:text-zinc-900">{{ __('Privacy Policy') }}</a>
                </div>
                <div class="mt-4 prose prose-sm print:hidden">
                    <p>{{ __('Powered by') }}&nbsp;<a href="https://github.com/avvertix/kart-race-management" target="_blank" rel="noopener">Kart Race Management</a></p>
                </div>
            </footer>
        </div>

        @stack('modals')

        @livewireScripts
    </body>
</html>
