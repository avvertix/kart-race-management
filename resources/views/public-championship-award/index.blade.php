<x-app-layout>
    <x-slot name="title">
        {{ __('Awards') }} - {{ $championship->title }}
    </x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight">
            {{ $championship->title }}
        </h2>
    </x-slot>

    <div class="pt-3 pb-6 px-4 sm:px-6 lg:px-8">

        <h3 class="text-lg font-bold mb-4">{{ __('Awards') }}</h3>

        @forelse ($groupedAwards as $typeName => $awards)
            <h4 class="font-semibold text-sm text-zinc-700 mt-8 mb-3">{{ $typeName }}</h4>

            <ul class="divide-y divide-zinc-200 border border-zinc-200 rounded-md">
                @foreach ($awards as $award)
                    <li>
                        <a href="{{ route('public.awards.show', $award) }}" class="flex items-center justify-between px-4 py-3 hover:bg-zinc-50 transition">
                            <span class="font-medium text-sm">{{ $award->name }}</span>
                            <span class="text-xs text-zinc-400">{{ $award->category?->name }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @empty
            <p class="text-zinc-600 p-4">{{ __('No awards.') }}</p>
        @endforelse
    </div>
</x-app-layout>
