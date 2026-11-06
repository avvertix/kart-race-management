<x-app-layout>
    <x-slot name="title">
        {{ __('Notifications') }}
    </x-slot>
    <x-slot name="header">
        <div class="flex flex-wrap items-center gap-3">
            <h2 class="flex-1 font-semibold text-xl text-zinc-800 leading-tight">{{ __('Notifications') }}</h2>
            @if ($items->contains('isRead', false))
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <x-secondary-button type="submit">{{ __('Mark all as read') }}</x-secondary-button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="pt-3 pb-12 px-4 sm:px-6 lg:px-8 space-y-4">
        @if ($items->isEmpty())
            <p class="rounded-md border border-dashed border-zinc-300 bg-white p-6 text-center text-sm text-zinc-600">
                {{ __('No notifications yet. You find here what needs your attention on licence requests and enrollments.') }}
            </p>
        @else
            <ul class="divide-y divide-zinc-200 rounded-md border border-zinc-200 bg-white">
                @foreach ($items as $item)
                    <li @class(['flex items-start gap-3 p-4 text-sm', 'bg-orange-50' => ! $item->isRead])>
                        <span @class(['mt-1.5 h-2 w-2 shrink-0 rounded-full', 'bg-orange-600' => ! $item->isRead, 'bg-transparent' => $item->isRead]) aria-hidden="true"></span>
                        <div class="min-w-0 flex-1">
                            @if ($item->url)
                                <a href="{{ route('notifications.open', $item->id) }}" @class(['text-zinc-900 hover:text-orange-700', 'font-semibold' => ! $item->isRead, 'font-medium' => $item->isRead])>
                                    {{ $item->title }}
                                    @unless ($item->isRead)
                                        <span class="sr-only">({{ __('unread') }})</span>
                                    @endunless
                                </a>
                            @else
                                <span class="font-medium text-zinc-700">{{ $item->title }}</span>
                            @endif
                            <p class="text-zinc-600 break-words">{{ $item->detail ?? __('No longer available') }}</p>
                        </div>
                        <span class="shrink-0 text-zinc-500"><x-time :value="$item->createdAt" /></span>
                    </li>
                @endforeach
            </ul>

            {{ $notifications->links() }}
        @endif
    </div>
</x-app-layout>
