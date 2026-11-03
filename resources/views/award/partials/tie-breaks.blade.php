@foreach($tieBreaks as $tieBreak)
    @php
        $driver = $tieBreak['bib'].' '.$tieBreak['first_name'].' '.$tieBreak['last_name'];
    @endphp
    <span class="block text-xs text-zinc-500">
        @if($tieBreak['criterion'] === null)
            {{ __('Still tied with :driver after all tie-breaks', ['driver' => $driver]) }}
        @else
            {{ $tieBreak['position'] === 'above' ? __('Behind :driver', ['driver' => $driver]) : __('Ahead of :driver', ['driver' => $driver]) }}:
            {{ trans("award.tie_breakers.{$tieBreak['criterion']}") }}
            {{ $tieBreak['value'] ?? '—' }} {{ __('vs') }} {{ $tieBreak['other_value'] ?? '—' }}
        @endif
    </span>
@endforeach
