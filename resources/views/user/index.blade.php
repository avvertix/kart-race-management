<x-app-layout>
    <x-slot name="title">
        {{ __('Users') }}
    </x-slot>

    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-zinc-800 leading-tight">
                {{ __('Users') }}
            </h2>
            @can('create', \App\Models\User::class)
                <a href="{{ route('users.create') }}" class="inline-flex items-center px-4 py-2 bg-zinc-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-zinc-700 focus:bg-zinc-700 active:bg-zinc-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('Create a user') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="pb-12">
        <div class="px-4 sm:px-6 lg:px-8">

            <div class="p-4 bg-white rounded mb-6">
                <form action="{{ route('users.index') }}" method="GET" class="flex gap-3 items-end">
                    <div class="flex-1">
                        <x-label for="search" value="{{ __('Search') }}" />
                        <x-input id="search" class="block mt-1 w-full" type="text" name="search" :value="$search" placeholder="{{ __('Search by name or email...') }}" />
                    </div>
                    <div>
                        <x-button type="submit">
                            {{ __('Search') }}
                        </x-button>
                    </div>
                    @if ($search)
                        <div>
                            <a href="{{ route('users.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                                {{ __('Clear') }}
                            </a>
                        </div>
                    @endif
                </form>
            </div>

            <table class="w-full text-sm bg-white rounded">
                <thead>
                    <tr>
                        <td class="px-4 py-3 border-b text-xs font-medium text-zinc-500 uppercase tracking-wider">{{ __('Name') }}</td>
                        <td class="px-4 py-3 border-b text-xs font-medium text-zinc-500 uppercase tracking-wider">{{ __('Email') }}</td>
                        <td class="px-4 py-3 border-b text-xs font-medium text-zinc-500 uppercase tracking-wider">{{ __('Email verified') }}</td>
                        <td class="px-4 py-3 border-b text-xs font-medium text-zinc-500 uppercase tracking-wider">{{ __('Role') }}</td>
                        <td class="px-4 py-3 border-b text-xs font-medium text-zinc-500 uppercase tracking-wider"></td>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td class="px-4 py-3 border-b">
                                {{ $user->name }}
                            </td>
                            <td class="px-4 py-3 border-b">
                                {{ $user->email }}
                            </td>
                            <td class="px-4 py-3 border-b">
                                @if ($user->hasVerifiedEmail())
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        {{ __('Verified') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        {{ __('Pending') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 border-b">
                                {{ $user->userRole()?->name ?? $user->role }}
                            </td>
                            <td class="px-4 py-3 border-b">
                                @can('update', $user)
                                    <a class="underline" href="{{ route('users.edit', $user) }}">{{ __('Edit') }}</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                @if ($search)
                                    <p class="text-zinc-600 p-4">{{ __('No users found matching ":search".', ['search' => $search]) }}</p>
                                @else
                                    <p class="text-zinc-600 p-4">{{ __('No users.') }}</p>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

        </div>
    </div>
</x-app-layout>
