<div class="space-y-4">
    <input type="hidden" name="bib" value="{{ $selected }}">

    <div class="flex flex-wrap items-center justify-between gap-2">
        <h3 class="text-sm font-medium text-zinc-900">{{ __('Race number') }}</h3>
        <ul class="flex flex-wrap gap-3 text-xs text-zinc-600" aria-label="{{ __('Legend') }}">
            <li class="flex items-center gap-1"><span class="inline-block size-3 rounded-sm border border-zinc-300 bg-white"></span>{{ __('free') }}</li>
            <li class="flex items-center gap-1"><span class="inline-block size-3 rounded-sm bg-orange-200"></span>{{ __('reserved') }}</li>
            <li class="flex items-center gap-1"><span class="inline-block size-3 rounded-sm bg-zinc-300"></span>{{ __('already used') }}</li>
            <li class="flex items-center gap-1"><span class="inline-block size-3 rounded-sm bg-lime-600"></span>{{ __('your pick') }}</li>
        </ul>
    </div>

    @foreach ($heldNumbers as $held)
        <p class="rounded-md border border-lime-300 bg-lime-50 p-2 text-sm text-lime-900">{{ $held }} {{ __('No need to pick another one.') }}</p>
    @endforeach

    <div class="grid grid-cols-2 gap-2 sm:grid-cols-5">
        @foreach ($hundreds as $item)
            <button type="button" wire:click="zoom({{ $item['hundred'] }})" aria-pressed="{{ $hundred === $item['hundred'] ? 'true' : 'false' }}" @class([
                'rounded-md border p-2 text-left focus:outline-none focus-visible:ring-3 focus-visible:ring-orange-300',
                'border-orange-400 bg-orange-50' => $hundred === $item['hundred'],
                'border-zinc-200 bg-white hover:border-orange-300' => $hundred !== $item['hundred'],
            ])>
                <span class="block font-mono text-sm font-semibold text-zinc-900">{{ $item['from'] }}–{{ $item['to'] }}</span>
                <span class="block text-xs text-zinc-600">{{ __(':count free', ['count' => $item['free']]) }}</span>
            </button>
        @endforeach
    </div>

    <div class="grid grid-cols-5 gap-1 sm:grid-cols-10" role="group" aria-label="{{ __('Race numbers :from to :to', ['from' => $numbers[0], 'to' => end($numbers)]) }}">
        @foreach ($numbers as $number)
            @php
                $state = $availability->state($number);
                $isFree = $state === \App\Enums\RaceNumberState::Free;
                $isPicked = $selected === $number;
                $holder = $showHolders ? $availability->holder($number) : null;
            @endphp
            <button type="button"
                wire:click="pick({{ $number }})"
                @unless ($isFree) aria-disabled="true" @endunless
                aria-pressed="{{ $isPicked ? 'true' : 'false' }}"
                aria-label="{{ $number }}, {{ $isPicked ? __('your pick') : $state->label() }}"
                @if ($holder) title="{{ $holder }}" @endif
                @class([
                    'min-h-10 rounded-sm border font-mono text-sm tabular-nums focus:outline-none focus-visible:ring-3 focus-visible:ring-orange-300',
                    'border-lime-700 bg-lime-600 text-white' => $isPicked,
                    'border-zinc-300 bg-white text-zinc-900 hover:border-orange-400' => $isFree && ! $isPicked,
                    'cursor-not-allowed border-orange-200 bg-orange-200 text-orange-900' => $state === \App\Enums\RaceNumberState::Reserved && ! $isPicked,
                    'cursor-not-allowed border-zinc-300 bg-zinc-300 text-zinc-600' => $state === \App\Enums\RaceNumberState::Used && ! $isPicked,
                ])>{{ $number }}</button>
        @endforeach
    </div>

    <div class="flex flex-wrap items-end gap-2">
        <div>
            <x-label for="race-number-jump" value="{{ __('Or type a number') }}" />
            <x-input id="race-number-jump" type="number" inputmode="numeric" min="1" max="999" wire:model="jumpTo" wire:keydown.enter.prevent="jump" class="mt-1 block w-32" />
        </div>
        <x-secondary-button type="button" wire:click="jump">{{ __('Go') }}</x-secondary-button>
    </div>
    @error('jumpTo')
        <p class="text-sm text-red-600">{{ $message }}</p>
    @enderror

    @if ($suggestions->isNotEmpty())
        <div class="space-y-1">
            <p class="text-sm text-zinc-600">{{ __('First free numbers') }}</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($suggestions as $number)
                    <button type="button" wire:click="pick({{ $number }})" class="min-h-10 min-w-10 rounded-sm border border-zinc-300 bg-white px-2 font-mono text-sm hover:border-orange-400 focus:outline-none focus-visible:ring-3 focus-visible:ring-orange-300">{{ $number }}</button>
                @endforeach
            </div>
        </div>
    @endif

    @if ($previousNumbers->isNotEmpty())
        <div class="space-y-1">
            <p class="text-sm text-zinc-600">{{ __('Numbers you used before') }}</p>
            <ul class="flex flex-wrap gap-2">
                @foreach ($previousNumbers as $participant)
                    @php($isFree = $availability->isFree((int) $participant->bib))
                    <li>
                        <button type="button"
                            wire:click="pick({{ (int) $participant->bib }})"
                            @unless ($isFree) aria-disabled="true" @endunless
                            @class([
                                'rounded-sm border px-2 py-1 text-left text-sm focus:outline-none focus-visible:ring-3 focus-visible:ring-orange-300',
                                'border-zinc-300 bg-white hover:border-orange-400' => $isFree,
                                'cursor-not-allowed border-zinc-200 bg-zinc-100 text-zinc-500' => ! $isFree,
                            ])>
                            <span class="font-mono font-semibold">{{ $participant->bib }}</span>
                            <span class="block text-xs">{{ $championshipTitles->get($participant->championship_id) }} &middot; {{ $participant->first_name }} {{ $participant->last_name }}</span>
                            @unless ($isFree)
                                <span class="block text-xs">{{ $availability->state((int) $participant->bib)->label() }}</span>
                            @endunless
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <p class="text-sm text-zinc-900" role="status">
        @if ($notice)
            {{ $notice }}
        @elseif ($selected)
            {{ __('Your pick: :bib. It is held for you until the organizer confirms the enrollment; numbers are first confirmed, first served.', ['bib' => $selected]) }}
        @else
            {{ __('Pick a free number.') }}
        @endif
    </p>
</div>
