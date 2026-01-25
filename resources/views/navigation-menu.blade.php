<nav x-data="{ open: false }" class="">
    {{-- Primary Navigation Menu --}}
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                {{-- Logo --}}
                <div class="shrink-0 flex items-center">
                    <a href="{{ auth()->check() ? route('dashboard') : route('welcome') }}">
                        <x-application-mark class="block h-8 w-auto" /><span class="sr-only">{{ __('Organizer Dashboard') }}</span>
                    </a>
                </div>

                {{-- Navigation Links --}}
                <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex print:hidden">

                    @auth
                        @can('viewAny', \App\Models\Championship::class)
                        <x-nav-link href="{{ route('championships.index') }}" :active="request()->routeIs('championships.*') || request()->routeIs('categories.*') || request()->routeIs('races.*') || request()->routeIs('participants.*')">
                            {{ __('Championships') }}
                        </x-nav-link>
                        @endcan
                        @can('viewAny', \App\Models\CommunicationMessage::class)
                            <x-nav-link href="{{ route('communications.index') }}" :active="request()->routeIs('communications.*')">
                                {{ __('Communications') }}
                            </x-nav-link>
                        @endcan
                        @can('viewAny', \App\Models\OrbitsBackup::class)
                            <x-nav-link href="{{ route('orbits-backups.index') }}" :active="request()->routeIs('orbits-backups.*')">
                                {{ __('Backups') }}
                            </x-nav-link>
                        @endcan
                        @can('viewAny', \App\Models\User::class)
                            <x-nav-link href="{{ route('users.index') }}" :active="request()->routeIs('users.*')">
                                {{ __('Users') }}
                            </x-nav-link>
                        @endcan
                    @endauth
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ml-6 print:hidden">

                {{-- Language selector --}}
                <x-language-selector />

                {{-- Settings Dropdown --}}
                @auth
                    <div class="ml-3 relative">
                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                                    <button class="flex text-sm border-2 border-transparent rounded-full focus:outline-none focus:border-zinc-300 transition">
                                        <img class="h-8 w-8 rounded-full object-cover" src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" />
                                    </button>
                                @else
                                    <span class="inline-flex rounded-md">
                                        <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-zinc-500 bg-white hover:text-zinc-700 focus:outline-none transition">
                                            {{ Auth::user()->name }}

                                            <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </span>
                                @endif
                            </x-slot>

                            <x-slot name="content">
                                {{-- Account Management --}}
                                <div class="block px-4 py-2 text-xs text-zinc-400">
                                    {{ __('Manage Account') }}
                                </div>

                                <x-dropdown-link href="{{ route('profile.show') }}">
                                    {{ __('Profile') }}
                                </x-dropdown-link>

                                <div class="border-t border-zinc-100"></div>

                                {{-- Log out --}}
                                <form method="POST" action="{{ route('logout') }}" x-data>
                                    @csrf

                                    <x-dropdown-link href="{{ route('logout') }}"
                                            @click.prevent="$root.submit();">
                                        {{ __('Log Out') }}
                                    </x-dropdown-link>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>
                @endauth
            </div>

            {{-- Hamburger --}}
            <div class="-mr-2 flex items-center sm:hidden print:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-zinc-400 hover:text-zinc-500 hover:bg-zinc-100 focus:outline-none focus:bg-zinc-100 focus:text-zinc-500 transition">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Responsive Navigation Menu --}}
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden bg-white print:hidden">
        <div class="pt-2 pb-3 space-y-1">
            @auth
                @can('viewAny', \App\Models\Championship::class)
                    <x-responsive-nav-link href="{{ route('championships.index') }}" :active="request()->routeIs('championships.*') || request()->routeIs('categories.*') || request()->routeIs('races.*') || request()->routeIs('participants.*')">
                        {{ __('Championships') }}
                    </x-responsive-nav-link>
                @endcan
                @can('viewAny', \App\Models\CommunicationMessage::class)
                    <x-responsive-nav-link href="{{ route('communications.index') }}" :active="request()->routeIs('communications.*')">
                        {{ __('Communications') }}
                    </x-responsive-nav-link>
                @endcan
                @can('viewAny', \App\Models\OrbitsBackup::class)
                    <x-responsive-nav-link href="{{ route('orbits-backups.index') }}" :active="request()->routeIs('orbits-backups.*')">
                        {{ __('Backups') }}
                    </x-responsive-nav-link>
                @endcan
                @can('viewAny', \App\Models\User::class)
                    <x-responsive-nav-link href="{{ route('users.index') }}" :active="request()->routeIs('users.*')">
                        {{ __('Users') }}
                    </x-responsive-nav-link>
                @endcan
            @endauth
        </div>
        
        @auth
            {{-- Responsive Settings Options --}}
            <div class="pt-4 pb-1 border-t border-zinc-200 print:hidden">
                <div class="flex items-center px-4">
                    @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                        <div class="shrink-0 mr-3">
                            <img class="h-10 w-10 rounded-full object-cover" src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" />
                        </div>
                    @endif

                    <div>
                        <div class="font-medium text-base text-zinc-800">{{ Auth::user()->name }}</div>
                        <div class="font-medium text-sm text-zinc-500">{{ Auth::user()->email }}</div>
                    </div>
                </div>

                <div class="mt-3 space-y-1">
                    {{-- Account Management --}}
                    <x-responsive-nav-link href="{{ route('profile.show') }}" :active="request()->routeIs('profile.show')">
                        {{ __('Profile') }}
                    </x-responsive-nav-link>

                    @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
                        <x-responsive-nav-link href="{{ route('api-tokens.index') }}" :active="request()->routeIs('api-tokens.index')">
                            {{ __('API Tokens') }}
                        </x-responsive-nav-link>
                    @endif

                    {{-- Authentication --}}
                    <form method="POST" action="{{ route('logout') }}" x-data>
                        @csrf

                        <x-responsive-nav-link href="{{ route('logout') }}"
                                    @click.prevent="$root.submit();">
                            {{ __('Log Out') }}
                        </x-responsive-nav-link>
                    </form>
                </div>
            </div>
        @endauth
    </div>
</nav>
