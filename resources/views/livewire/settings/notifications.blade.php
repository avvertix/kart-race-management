<section class="w-full px-4 sm:px-6 lg:px-8">
    @include('partials.settings-heading')

    <flux:heading level="2" class="sr-only">{{ __('Notification settings') }}</flux:heading>

    <x-settings.layout :heading="__('Notifications')" :subheading="__('Choose which notifications you also receive by email. Changes are saved right away; you always find the notifications in the app.')">
        <div class="my-6 w-full">
            <ul class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach ($types as $type)
                    <li wire:key="notification-{{ $type->value }}" class="py-3">
                        <flux:switch
                            wire:model.live="emails.{{ $type->value }}"
                            :label="$type->label()"
                            :description="$isAdmin && $type->isForOrganizers()
                                ? $type->description().' '.__('Admins receive it only with the email on.')
                                : $type->description()"
                        />
                    </li>
                @endforeach
            </ul>
        </div>
    </x-settings.layout>
</section>
