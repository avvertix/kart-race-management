{{--
    The race number picker, see App\View\Components\RaceNumberPicker: picking happens in the browser, the form sends `bib`.
    The numbers used before come first, then the hundreds and their numbers.
    The grid has one tab stop: arrow keys, Home and End move between its numbers.
--}}
@php
    $sectionLabel = 'block text-sm font-medium text-zinc-800';
    $reservedLook = 'cursor-not-allowed border-zinc-200 bg-[repeating-linear-gradient(135deg,var(--color-zinc-200)_0_3px,var(--color-white)_3px_7px)] text-zinc-600 line-through';
    $usedLook = 'cursor-not-allowed border-zinc-300 bg-zinc-300 text-zinc-600';
@endphp

<div class="space-y-5"
    x-data="{
        states: {{ Js::from($states) }},
        holders: {{ Js::from((object) $holders) }},
        labels: {{ Js::from($labels) }},
        hundred: {{ Js::from($hundred) }},
        selected: {{ Js::from($selected) }},
        focusBib: null,
        notice: '',
        state(bib) { return this.states[bib] ?? 'x' },
        isFree(bib) { return bib >= 1 && bib <= 999 && this.state(bib) === 'f' },
        get numbers() {
            const list = [];
            for (let bib = Math.max(1, this.hundred * 100); bib <= this.hundred * 100 + 99; bib++) list.push(bib);
            return list;
        },
        get tabBib() {
            if (this.numbers.includes(this.focusBib)) return this.focusBib;
            return this.numbers.includes(this.selected) ? this.selected : this.numbers[0];
        },
        label(bib) { return bib + ', ' + (this.selected === bib ? this.labels.pick : this.labels.states[this.state(bib)]) },
        pick(bib) {
            if (! this.isFree(bib)) {
                this.notice = bib < 1 || bib > 999
                    ? this.labels.outOfRange
                    : this.labels.taken.replace(':bib', bib).replace(':state', this.labels.states[this.state(bib)]);
                return;
            }
            this.selected = bib;
            this.notice = '';
        },
        open(bib) {
            this.hundred = Math.floor(bib / 100);
            this.pick(bib);
        },
        move(event, bib) {
            const columns = window.matchMedia('(min-width: 40rem)').matches ? 10 : 5;
            const steps = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -columns, ArrowDown: columns };
            const first = this.numbers[0];
            const last = this.numbers[this.numbers.length - 1];
            let target;
            if (event.key in steps) target = bib + steps[event.key];
            else if (event.key === 'Home') target = first;
            else if (event.key === 'End') target = last;
            else return;
            event.preventDefault();
            this.focusBib = Math.min(Math.max(target, first), last);
            this.$nextTick(() => this.$root.querySelector('[data-bib=\'' + this.focusBib + '\']')?.focus());
        },
        get status() {
            if (this.notice) return this.notice;
            return this.selected ? this.labels.picked.replace(':bib', this.selected) : this.labels.nothing;
        },
    }">
    <input type="hidden" name="bib" value="{{ $selected }}" x-bind:value="selected ?? ''">

    <div class="space-y-2">
        <h3 class="text-sm font-medium text-zinc-900">
            {{ __('Race number') }}
            <span class="tabular-nums text-lime-800" x-show="selected" x-cloak>&middot; <span x-text="labels.pickShort.replace(':bib', selected)"></span></span>
        </h3>
        <ul class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-zinc-600" aria-label="{{ __('Legend') }}">
            <li class="flex items-center gap-1"><span class="inline-block size-3 rounded-sm border border-zinc-300 bg-white"></span>{{ __('free') }}</li>
            <li class="flex items-center gap-1"><span class="inline-block size-3 rounded-sm border border-zinc-300 bg-[repeating-linear-gradient(135deg,var(--color-zinc-300)_0_2px,var(--color-white)_2px_4px)]"></span>{{ __('reserved') }}</li>
            <li class="flex items-center gap-1"><span class="inline-block size-3 rounded-sm bg-zinc-300"></span>{{ __('already used') }}</li>
            <li class="flex items-center gap-1"><span class="inline-block size-3 rounded-sm bg-lime-600"></span>{{ __('your pick') }}</li>
        </ul>
    </div>

    @foreach ($heldNumbers as $held)
        <p class="rounded-md border border-lime-300 bg-lime-50 p-2 text-sm text-lime-900">{{ $held }} {{ __('No need to pick another one.') }}</p>
    @endforeach

    @if ($previousNumbers->isNotEmpty())
        <div class="space-y-2">
            <p class="{{ $sectionLabel }}">{{ __('Numbers you used before') }}</p>
            <ul class="flex flex-wrap gap-2">
                @foreach ($previousNumbers as $previous)
                    @php($isFree = $previous['state'] === \App\Enums\RaceNumberState::Free)
                    <li>
                        <button type="button"
                            x-on:click="open({{ $previous['bib'] }})"
                            @unless ($isFree) aria-disabled="true" @endunless
                            @class([
                                'min-h-11 rounded-md border px-3 py-1.5 text-left text-sm focus:outline-none focus-visible:ring-3 focus-visible:ring-orange-300',
                                'border-zinc-300 bg-white text-zinc-900 hover:border-orange-400' => $isFree,
                                'cursor-not-allowed border-zinc-200 bg-zinc-100 text-zinc-600' => ! $isFree,
                            ])>
                            <span class="font-semibold tabular-nums">{{ $previous['bib'] }}</span>
                            <span class="block text-xs">{{ $previous['championship'] }} &middot; {{ $previous['driver'] }}</span>
                            @if ($previous['note'])
                                <span class="block text-xs">{{ $previous['note'] }}</span>
                            @endif
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div @class(['space-y-2', 'border-t border-zinc-200 pt-4' => $previousNumbers->isNotEmpty()])>
        @if ($previousNumbers->isNotEmpty())
            <p class="{{ $sectionLabel }}">{{ __('Or browse the numbers') }}</p>
        @endif

        <div class="grid grid-cols-2 gap-2 sm:grid-cols-5">
            @foreach ($hundreds as $item)
                <button type="button"
                    x-on:click="hundred = {{ $item['hundred'] }}; focusBib = null"
                    x-bind:aria-pressed="hundred === {{ $item['hundred'] }} ? 'true' : 'false'"
                    aria-pressed="{{ $hundred === $item['hundred'] ? 'true' : 'false' }}"
                    class="min-h-11 rounded-md border p-2 text-left focus:outline-none focus-visible:ring-3 focus-visible:ring-orange-300"
                    x-bind:class="hundred === {{ $item['hundred'] }} ? 'border-orange-400 bg-orange-50' : 'border-zinc-200 bg-white hover:border-orange-300'">
                    <span class="block text-sm font-semibold tabular-nums text-zinc-900">{{ $item['from'] }}–{{ $item['to'] }}</span>
                    <span class="block text-xs text-zinc-600">{{ __(':count free', ['count' => $item['free']]) }}</span>
                </button>
            @endforeach
        </div>

        {{-- Room for 10 rows of 44px buttons: 20 rows of 5 on phones, 10 rows of 10 from sm, so the page does not jump when they appear. --}}
        <div class="grid min-h-[59.75rem] grid-cols-5 content-start gap-1 sm:min-h-[29.75rem] sm:grid-cols-10" role="group" x-bind:aria-label="labels.range.replace(':from', numbers[0]).replace(':to', numbers[numbers.length - 1])">
            <template x-for="bib in numbers" :key="bib">
                <button type="button"
                    x-on:click="pick(bib)"
                    x-on:keydown="move($event, bib)"
                    x-on:focus="focusBib = bib"
                    x-text="bib"
                    x-bind:data-bib="bib"
                    x-bind:tabindex="bib === tabBib ? 0 : -1"
                    x-bind:aria-label="label(bib)"
                    x-bind:aria-pressed="selected === bib ? 'true' : 'false'"
                    x-bind:aria-disabled="isFree(bib) ? 'false' : 'true'"
                    x-bind:title="holders[bib] ?? ''"
                    class="min-h-11 rounded-sm border text-sm tabular-nums focus:outline-none focus-visible:ring-3 focus-visible:ring-orange-300"
                    x-bind:class="{
                        'border-lime-700 bg-lime-600 text-white': selected === bib,
                        'border-zinc-300 bg-white text-zinc-900 hover:border-orange-400': selected !== bib && state(bib) === 'f',
                        '{{ $reservedLook }}': selected !== bib && state(bib) === 'r',
                        '{{ $usedLook }}': selected !== bib && state(bib) === 'u',
                    }"></button>
            </template>
        </div>

        <p class="min-h-5 text-sm text-zinc-900" role="status" x-text="status" x-bind:class="notice ? 'text-amber-800' : 'text-zinc-900'">
            {{ $selected ? str_replace(':bib', (string) $selected, $labels['picked']) : $labels['nothing'] }}
        </p>
    </div>
</div>
