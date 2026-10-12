<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-100 font-sans text-zinc-900 antialiased">
        <div class="flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-sm flex-col gap-2">
                <a href="{{ route('welcome') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                    <x-app-logo-icon class="h-14 mb-1" />
                    <span class="sr-only">{{ config('app.name', 'KRM') }}</span>
                </a>
                <div class="flex flex-col gap-6 rounded-lg bg-white p-6 shadow-md">
                    {{ $slot }}
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
