<div {{ $attributes }}>
    <p class="uppercase tracking-widest text-sm mb-2">{{ __('Races currently active or available for registration') }}</p>

    <div class="grid grid-cols-3 gap-4">

        @forelse ($races as $item)
            <article class="p-4 shadow-lg bg-white rounded-md ring-2 ring-orange-300 shadow-orange-200">
                <h1 class="text-2xl font-bold mb-1"><a href="{{ route('races.show', $item) }}" class="text-orange-600 hover:text-orange-900">{{ $item->title }}</a></h1>
                <p class="text-zinc-700 mb-1">{{ $item->period }}</p>
                <p class="text-zinc-700">{{ $item->track }}</p>
                <p class="text-zinc-700"><x-race-status :value="$item->status" /></p>
            </article>
        @empty
            <article class="p-4 shadow-lg bg-white rounded-md ring-2 ring-orange-300 ">
                <p class="text-zinc-700 mb-1">{{ __('No race available for self registration open or currently active.') }}</p>
                <p class="text-zinc-700">{{ __('You can still browse all races and participants, but racers cannot sign-up yet.') }}</p>
            </article>
        @endforelse
    </div>

</div>