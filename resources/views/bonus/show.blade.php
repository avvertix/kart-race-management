<x-championship-page-layout :$championship>
    <x-slot name="title">
        {{$bonus->driver}} - {{ __('Bonus') }} - {{ $championship->title }}
    </x-slot>
    <x-slot name="actions">
        @can('update', $bonus)
            <x-button-link href="{{ route('bonuses.edit', $bonus) }}">
                {{ __('Edit bonus') }}
            </x-button-link>
        @endcan
    </x-slot>

        <div class="grid md:grid-cols-2 gap-4">

            <div class="p-4 bg-white shadow-xl rounded space-y-4">
                <div>
                    <p class="text-lg font-semibold">{{ $bonus->driver }}</p>
                    <p class="font-mono text-sm text-zinc-600">{{ $bonus->driver_licence }}</p>
                </div>

                <div class="space-y-1">
                    <p class="text-sm text-zinc-600">{{ __('Bonus type') }}</p>
                    <p class="font-medium">{{ $bonus->bonus_type->localizedName() }}</p>
                </div>

                @if($bonus_mode === \App\Models\BonusMode::CREDIT)
                    <div class="space-y-1">
                        <p class="text-sm text-zinc-600">{{ __('Credits') }}</p>
                        <p class="font-mono">
                            <span class="font-bold text-lg">{{ $bonus->amount }}</span> - <x-price>{{ $bonus->amount * $fixed_bonus_amount }}</x-price>
                        </p>
                    </div>

                    <div class="space-y-1">
                        <p class="text-sm text-zinc-600">{{ __('Used') }}</p>
                        <p class="font-medium">{{ trans_choice(':value time|:value times', $bonus->usages_count, ['value' => $bonus->usages_count]) }}</p>
                    </div>

                    <div class="space-y-1">
                        <p class="text-sm text-zinc-600">{{ __('Remaining') }}</p>
                        <p class="font-bold text-lg tabular-nums">{{ $bonus->remaining }}</p>
                    </div>
                @else
                    <div class="space-y-1">
                        <p class="text-sm text-zinc-600">{{ __('Balance') }}</p>
                        <p class="font-bold text-lg"><x-price>{{ $bonus->amount }}</x-price></p>
                    </div>

                    <div class="space-y-1">
                        <p class="text-sm text-zinc-600">{{ __('Used') }}</p>
                        <p class="font-medium">{{ trans_choice(':value time|:value times', $bonus->usages_count, ['value' => $bonus->usages_count]) }}</p>
                    </div>

                    <div class="space-y-1">
                        <p class="text-sm text-zinc-600">{{ __('Remaining') }}</p>
                        <p class="font-bold text-lg"><x-price>{{ $bonus->remaining }}</x-price></p>
                    </div>
                @endif
            </div>

            <div class="p-4 ">
                <h3 class="text-lg font-semibold mb-4">{{ __('Usage history') }}</h3>

                @if($bonusUsage->isEmpty())
                    <p class="text-zinc-500 text-sm">{{ __('No usage history yet') }}</p>
                @else
                    <div class="space-y-3">
                        @foreach ($bonusUsage as $participant)
                            <div class="border-l-2 border-orange-500 pl-3 py-1">
                                <p class="font-medium">
                                    <a href="{{ route('participants.show', $participant) }}" class="text-orange-600 hover:text-orange-900">
                                        {{ $participant->race->title }}
                                    </a>
                                </p>
                                <p class="text-xs text-zinc-500">
                                    {{ $participant->pivot->created_at->format('d/m/Y H:i') }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>

</x-championship-page-layout>
