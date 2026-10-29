<x-app-layout>
    <x-slot name="title">
        {{ __('Enrollments and offers') }}
    </x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight">
            {{ __('Enrollments and offers') }}
        </h2>
    </x-slot>

    <div class="pt-3 pb-12 px-4 sm:px-6 lg:px-8">
        <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($products as $product)
                @php
                    $currentPrice = $product->currentPrice();
                @endphp
                <li class="relative flex flex-col gap-2 rounded-md border border-zinc-200 bg-white p-4 shadow-xs hover:border-orange-300">
                    <p class="text-sm text-zinc-500">{{ $product->kind->label() }}</p>
                    <h3 class="font-semibold text-lg text-zinc-900">
                        <a href="{{ route('public.products.show', $product) }}">
                            <span class="absolute inset-0"></span>
                            {{ $product->title }}
                        </a>
                    </h3>
                    @if ($product->description)
                        <p class="text-sm text-zinc-600">{{ $product->description }}</p>
                    @endif
                    <p class="mt-auto">
                        @if ($currentPrice === null)
                            <span class="text-sm text-zinc-500">{{ __('Not available at the moment') }}</span>
                        @elseif ($currentPrice->isRange())
                            <span class="font-mono tabular-nums">{{ $currentPrice->label() }}</span>
                            <span class="block text-sm text-zinc-500">{{ __('final price after review') }}</span>
                        @else
                            <span class="font-mono tabular-nums">{{ $currentPrice->label() }}</span>
                        @endif
                    </p>
                </li>
            @empty
                <li class="text-zinc-600">{{ __('Nothing on sale at the moment.') }}</li>
            @endforelse
        </ul>
    </div>
</x-app-layout>
