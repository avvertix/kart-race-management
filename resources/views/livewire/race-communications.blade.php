<div class="flex gap-6 flex-col md:flex-row">

    @can('create', \App\Models\RaceCommunication::class)
    <div class="md:w-1/2 lg:w-1/3 bg-white shadow rounded p-4 sm:p-6">
        <h3 class="text-base font-semibold text-zinc-900 mb-4">{{ __('Post a message') }}</h3>

        <form wire:submit="post" class="space-y-4">

            <div class="flex flex-col gap-4">
                {{-- <div>
                    <x-label value="{{ __('Type') }}" />
                    <div class="mt-1 flex gap-3">
                        @foreach ($this->types as $t)
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" wire:model="type" value="{{ $t->value }}" class="text-orange-600 focus:ring-orange-500" />
                                <span class="text-sm">{{ $t->localizedName() }}</span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error for="type" class="mt-1" />
                </div> --}}

                <div>
                    <x-label value="{{ __('Session') }}" />
                    <div class="mt-1 flex flex-wrap gap-3">
                        @foreach ($this->runTypes as $rt)
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" wire:model="run_type" value="{{ $rt->value }}" class="text-orange-600 focus:ring-orange-500" />
                                <span class="text-sm">{{ $rt->localizedName() }}</span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error for="run_type" class="mt-1" />
                </div>

                <div>
                    <x-textarea id="message" wire:model="message" wire:keydown.ctrl.enter="post" class="mt-1 block w-full" rows="3" placeholder="{{ __('Write your message here…') }}" required />
                    <x-input-error for="message" class="mt-1" />
                </div>

                <div class="flex items-center gap-4">
                    <x-button type="submit" class="gap-2">
                        {{ __('Post') }} <span class="font-mono text-xs font-light bg-stone-500 px-1 py-0.5 rounded-sm inline-block">Ctrl+Enter</span>
                    </x-button>

                    <span wire:loading
                        wire:target="post"
                        x-transition
                        class="text-sm">
                        {{ __('Saving...') }}
                    </span>

                    <span x-data="{ shown: false }"
                        x-show="shown"
                        x-transition
                        x-init="@this.on('posted', () => { shown = true; setTimeout(() => shown = false, 2000); })"
                        class="text-sm text-green-600">
                        {{ __('Posted!') }}
                    </span>
                </div>

                <div>
                    <x-label value="{{ __('Saved templates') }}" />
                    <div class="mt-1 space-y-2 max-h-64 overflow-y-auto">
                        @foreach ($this->penalties as $penalty)
                            <div x-data="{ desc: @js($penalty->description ?? $penalty->title), copied: false }"
                                class="rounded border border-zinc-200 bg-zinc-50 p-3 flex flex-row gap-2 items-center">
                            <p class="grow text-sm font-medium text-zinc-800 truncate">
                                {{ $penalty->title }}

                                @if ($penalty->description)
                                <span class="block text-xs ">{{ $penalty->description }}</span>
                                @endif
                            </p>
                            <div class="flex gap-2">
                                <button
                                    type="button"
                                    x-on:click="navigator.clipboard.writeText(desc); copied = true; setTimeout(() => copied = false, 2000)"
                                    class="inline-flex items-center gap-1 rounded bg-white border border-zinc-200 px-2 py-0.5 text-xs text-zinc-600 hover:bg-zinc-100 transition"
                                >
                                    <x-ri-file-copy-line class="size-3" />
                                    <span x-show="!copied">{{ __('Copy') }}</span>
                                    <span x-show="copied" x-cloak class="text-green-600">{{ __('Copied!') }}</span>
                                </button>
                                <button
                                    type="button"
                                    x-on:click="
                                        let current = $wire.message;
                                        $wire.set('message', current.trim() === '' ? desc : current.trimEnd() + '\n' + desc);
                                    "
                                    class="inline-flex items-center gap-1 rounded bg-white border border-zinc-200 px-2 py-0.5 text-xs text-zinc-600 hover:bg-zinc-100 transition"
                                >
                                    <x-ri-corner-down-left-line class="size-3" />
                                    {{ __('Insert') }}
                                </button>
                            </div>
                        </div>
                    @endforeach
                    </div>
                </div>
            </div>
        </form>
    </div>
    @endcan

    <div class="grow space-y-3">
        @forelse ($this->communications as $item)
            <div x-data="{ copied: false }" @class([
                'bg-white shadow rounded p-4',
                'opacity-60 hover:opacity-100 focus-within:opacity-100' => $item->read_at,
            ])>
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            {{-- <span @class([
                                'inline-flex items-center rounded px-2 py-0.5 text-xs font-medium',
                                'bg-red-100 text-red-800' => $item->type === \App\Models\CommunicationType::Penalty,
                                'bg-blue-100 text-blue-800' => $item->type === \App\Models\CommunicationType::Communication,
                            ])>
                                {{ $item->type->localizedName() }}
                            </span> --}}
                            
                            <span class="text-xs text-zinc-400">{{ $item->created_at->diffForHumans() }}</span>
                            <span class="text-xs text-zinc-500">{{ $item->user?->name }}</span>
                        </div>
                        <p>
                            @if ($item->run_type)
                                <span class="inline-flex items-center rounded bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-700">
                                    {{ $item->run_type->localizedName() }}
                                </span>
                            @endif
                        </p>
                        <p class="mt-2 text-zinc-800 whitespace-pre-line">{{ $item->message }}</p>
                        @if ($item->read_at)
                            <p class="mt-1 text-xs text-zinc-400">{{ __('Read') }} {{ $item->read_at->diffForHumans() }}</p>
                        @endif
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <button
                            type="button"
                            x-on:click="navigator.clipboard.writeText(@js($item->message)); copied = true; setTimeout(() => copied = false, 2000)"
                            title="{{ __('Copy message') }}"
                            class="inline-flex items-center rounded px-2 py-1 text-xs font-medium transition bg-zinc-100 text-zinc-600 hover:bg-zinc-200"
                        >
                            <x-ri-file-copy-line class="size-3 mr-1" />
                            <span x-show="!copied">{{ __('Copy') }}</span>
                            <span x-show="copied" x-cloak class="text-green-600">{{ __('Copied!') }}</span>
                        </button>

                        @can('update', \App\Models\RaceCommunication::class)
                            <button
                                type="button"
                                wire:click="toggleRead('{{ $item->ulid }}')"
                                title="{{ $item->read_at ? __('Mark as unread') : __('Mark as read') }}"
                                @class([
                                    'inline-flex items-center rounded px-2 py-1 text-xs font-medium transition',
                                    'bg-green-100 text-green-700 hover:bg-green-200' => $item->read_at,
                                    'bg-zinc-100 text-zinc-600 hover:bg-zinc-200' => !$item->read_at,
                                ])
                            >
                                @if ($item->read_at)
                                    <x-ri-check-double-line class="size-4 mr-1" />
                                    {{ __('Read') }}
                                @else
                                    <x-ri-check-line class="size-4 mr-1" />
                                    {{ __('Mark read') }}
                                @endif
                            </button>
                        @endcan

                        @can('delete', $item)
                            <button
                                type="button"
                                wire:click="delete('{{ $item->ulid }}')"
                                wire:confirm="{{ __('Delete this message?') }}"
                                class="text-zinc-400 hover:text-red-600 transition"
                                title="{{ __('Delete') }}"
                            >
                                <x-ri-delete-bin-line class="size-4" />
                            </button>
                        @endcan
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-12 text-zinc-500">
                <p>{{ __('No messages yet.') }}</p>
            </div>
        @endforelse
    </div>

</div>
