<x-app-layout>
    <x-slot name="title">
        {{ __('Race number') }} - {{ $championship->title }}
    </x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight flex flex-wrap gap-2">
            <span><a href="{{ route('public.championships.enrollment', $championship) }}">{{ $championship->title }}</a></span>
            <span>/</span>
            <span>{{ __('Race number') }}</span>
        </h2>
        <p class="text-sm text-zinc-500">{{ $enrollment->holder()?->fullName() }}</p>
    </x-slot>

    <div class="pt-3 pb-12 px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('my-enrollments.race-number.update', $enrollment) }}" class="max-w-3xl space-y-4 rounded-md border border-zinc-200 bg-white p-4">
            @csrf
            @method('PUT')

            <x-validation-errors />

            <livewire:race-number-picker :championship="$championship" :enrollment="$enrollment" />

            <x-button type="submit">{{ __('Save race number') }}</x-button>
        </form>
    </div>
</x-app-layout>
