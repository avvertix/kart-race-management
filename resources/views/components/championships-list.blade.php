<div {{ $attributes->merge(['class' => 'space-y-3']) }}>
    <p data-section="true" class="font-mono text-xs/6 font-medium tracking-widest text-gray-600 uppercase">
        @if($canView)
            <a href="{{ route('championships.index') }}">{{ __('Championships') }}</a>
        @else
            {{ __('Championships') }}
        @endcan
    </p>

    @forelse ($championships as $championship)
        <article @class([
            'p-2 md:p-4 bg-white rounded group relative ring-0 space-y-1',
            'hover:ring-2 focus-within:ring-2 ring-orange-500' => $canView,
        ])>

        {{-- hover:ring-2 focus-within:ring-2 ring-orange-500 --}}

            @if ($canView)
                <a href="{{ route('championships.show', $championship) }}"
                    class=" font-bold  group-hover:text-orange-900 focus:text-orange-900 focus:outline-none">
                    <span class="z-10 absolute inset-0"></span>{{ $championship->title }}
                </a>
            @else
                <h1 
                    class=" font-bold ">
                    {{ $championship->title }}
                </h1>
                
            @endif

            <p class="text-sm text-zinc-700 tabular-nums">
                {{ trans_choice(':value race|:value races', $championship->races_count, ['value' => $championship->races_count]) }}
            </p>
            <p class="text-sm text-zinc-700"></p>
            <p class="hidden md:block text-sm text-zinc-700"></p>

        </article>
    @empty
        <div class="p-2 md:p-4 gap-3 bg-white rounded relative space-y-1">
            <p class="font-medium">{{ __('No championships.') }}</p>
            <p class="text-zinc-700">{{ __('No championships scheduled yet.') }}</p>
            <x-ri-steering-fill class="h-full w-auto text-zinc-200 absolute right-0 top-0" />
        </div>
    @endforelse
</div>
