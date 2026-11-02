{{-- The comments on $sections not sent yet, and the field to add one when $target is being commented --}}
@foreach ($openFlags->filter(fn ($flag) => in_array($flag->section, $sections, true)) as $flag)
    <div wire:key="flag-{{ $flag->getKey() }}" class="flex flex-wrap items-start gap-2 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
        <p class="flex-1 min-w-40"><span class="font-semibold">{{ __('Comment') }}:</span> {{ $flag->note }}</p>
        @if ($isReviewable)
            <button type="button" wire:click="removeSectionComment({{ $flag->getKey() }})" class="font-medium text-red-700 hover:text-red-900">{{ __('Remove') }}</button>
        @endif
    </div>
@endforeach

@include('livewire.licence-request-review.comment-form', ['target' => $target])
