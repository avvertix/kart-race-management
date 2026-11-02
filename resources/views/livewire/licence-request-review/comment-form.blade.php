{{-- The field to write a comment on $target, while it is the one being commented --}}
@if ($commentingOn === $target)
    <form wire:submit="saveComment" class="space-y-2" wire:key="comment-form-{{ $target }}">
        <x-label for="comment" value="{{ __('What needs fixing? The driver sees it when you request changes.') }}" />
        <x-textarea id="comment" wire:model="comment" rows="2" maxlength="1000" required autofocus />
        <x-input-error for="note" />
        <div class="flex gap-2">
            <x-button type="submit" size="sm">{{ __('Add comment') }}</x-button>
            <x-secondary-button type="button" size="sm" wire:click="cancelComment">{{ __('Cancel') }}</x-secondary-button>
        </div>
    </form>
@endif
