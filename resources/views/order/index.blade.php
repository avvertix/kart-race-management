<x-app-layout>
    <x-slot name="title">
        {{ __('Orders') }}
    </x-slot>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight">
            {{ __('Orders') }}
        </h2>
    </x-slot>

    <div class="pb-12">
        <div class="px-4 sm:px-6 lg:px-8 space-y-4">

            <nav class="flex flex-wrap gap-2 text-sm">
                <a href="{{ route('orders.index') }}" @class(['px-3 py-1 rounded-full border', 'border-orange-400 bg-orange-50 text-orange-800' => $status === null, 'border-zinc-200 text-zinc-700 hover:border-zinc-400' => $status !== null])>{{ __('All') }}</a>
                @foreach (\App\Enums\OrderStatus::cases() as $option)
                    <a href="{{ route('orders.index', ['status' => $option->value]) }}" @class(['px-3 py-1 rounded-full border', 'border-orange-400 bg-orange-50 text-orange-800' => $status === $option, 'border-zinc-200 text-zinc-700 hover:border-zinc-400' => $status !== $option])>{{ $option->label() }}</a>
                @endforeach
            </nav>

            <x-table>
                <x-slot name="head">
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-zinc-900 sm:pl-6">{{ __('Placed') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('User') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Products') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Total') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Status') }}</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                        <span class="sr-only">{{ __('Open') }}</span>
                    </th>
                </x-slot>

                @forelse ($orders as $item)
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm text-zinc-500 sm:pl-6"><x-time :value="$item->created_at" /></td>
                        <td class="px-3 py-4 text-sm text-zinc-900">{{ $item->user?->name }}</td>
                        <td class="px-3 py-4 text-sm text-zinc-500">{{ $item->lines->pluck('description')->join(', ') }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500 font-mono tabular-nums">{{ $item->total === null ? '—' : $item->currency->format($item->total) }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">{{ $item->status->label() }}</td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                            <a href="{{ route('orders.show', $item) }}" class="text-orange-600 hover:text-orange-900">{{ __('Open') }}<span class="sr-only">, {{ $item->ulid }}</span></a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-3 py-4 space-y-2 text-center">
                            <p>{{ __('No orders at the moment') }}</p>
                        </td>
                    </tr>
                @endforelse
            </x-table>

            {{ $orders->links() }}

        </div>
    </div>
</x-app-layout>
