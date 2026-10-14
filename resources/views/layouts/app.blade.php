<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')

        @livewireStyles
    </head>
    <body class="min-h-screen bg-zinc-100 font-sans text-zinc-900 antialiased print:bg-white">
        <flux:header class="border-b border-zinc-200 bg-white print:hidden">
            <flux:sidebar.toggle class="lg:hidden mr-2" icon="bars-2" inset="left" />

            <a href="{{ auth()->check() ? route('dashboard') : route('welcome') }}" class="me-6 flex items-center">
                <x-application-mark class="block h-8 w-auto" />
                <span class="sr-only">{{ __('Organizer Dashboard') }}</span>
            </a>

            <flux:navbar class="-mb-px max-lg:hidden">
                @include('partials.navigation', ['component' => 'navbar'])
            </flux:navbar>

            <flux:spacer />

            <x-language-selector class="max-lg:hidden" />

            @auth
                <flux:dropdown position="bottom" align="end">
                    <flux:profile circle :initials="auth()->user()->initials()" :chevron="false" />

                    <flux:menu class="w-64">
                        <div class="flex items-center gap-3 px-3 py-2">
                            <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" />
                            <div class="grid min-w-0 flex-1 text-start text-sm leading-tight">
                                <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                            </div>
                        </div>

                        <flux:menu.separator />

                        <flux:menu.item :href="route('profile.edit')" icon="cog-6-tooth" icon:variant="outline">{{ __('Settings') }}</flux:menu.item>

                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" icon:variant="outline" class="w-full">
                                {{ __('Log Out') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            @endauth

            @guest
                <div class="flex items-center gap-2 max-lg:hidden">
                    @if (Route::has('login'))
                        <flux:button variant="ghost" :href="route('login')">{{ __('Sign in') }}</flux:button>
                    @endif

                    @if (Route::has('register'))
                        <flux:button variant="primary" :href="route('register')">{{ __('Register') }}</flux:button>
                    @endif
                </div>
            @endguest
        </flux:header>

        {{-- Mobile navigation --}}
        <flux:sidebar collapsible="mobile" sticky class="lg:hidden border-e border-zinc-200 bg-white print:hidden">
            <flux:sidebar.header>
                <a href="{{ auth()->check() ? route('dashboard') : route('welcome') }}">
                    <x-application-mark class="block h-8 w-auto" />
                    <span class="sr-only">{{ __('Organizer Dashboard') }}</span>
                </a>
                <flux:sidebar.collapse />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                @include('partials.navigation', ['component' => 'sidebar'])
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                @guest
                    @if (Route::has('login'))
                        <flux:sidebar.item :href="route('login')">{{ __('Sign in') }}</flux:sidebar.item>
                    @endif

                    @if (Route::has('register'))
                        <flux:sidebar.item :href="route('register')">{{ __('Register') }}</flux:sidebar.item>
                    @endif
                @endguest

                <flux:sidebar.item :href="route('language.change', ['lang' => 'it'])" :current="app()->getLocale() === 'it'">{{ __('Italiano') }}</flux:sidebar.item>
                <flux:sidebar.item :href="route('language.change', ['lang' => 'en'])" :current="app()->getLocale() === 'en'">{{ __('English') }}</flux:sidebar.item>
            </flux:sidebar.nav>
        </flux:sidebar>

        <x-banner />

        {{-- Page Heading --}}
        @if (isset($header))
            <header>
                <div {{ $header->attributes->class(['py-6', 'px-4', 'sm:px-6', 'lg:px-8', 'print:py-2']) }}>
                    {{ $header }}
                </div>
            </header>
        @endif

        <x-broadcast-communications />

        {{-- Page Content --}}
        <main>
            {{ $slot }}
        </main>

        <footer class="mt-10 px-4 pb-6 sm:px-6 lg:px-8">
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

        @stack('modals')

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @livewireScripts
        @fluxScripts
    </body>
</html>
