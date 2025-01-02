<?php

declare(strict_types=1);

use App\Models\Championship;
use TimoKoerber\LaravelOneTimeOperations\OneTimeOperation;

return new class extends OneTimeOperation
{
    /**
     * Determine if the operation is being processed asynchronously.
     */
    protected bool $async = false;

    /**
     * The queue that the job will be dispatched to.
     */
    protected string $queue = 'default';

    /**
     * A tag name, that this operation can be filtered by.
     */
    protected ?string $tag = null;

    /**
     * Process the operation.
     */
    public function process(): void
    {
        $tires = collect(config('races.tires'))->map(function ($value, $key) {
            return [
                'code' => $key,
                ...$value,
            ];
        })->filter();

        if ($tires->isEmpty()) {
            return;
        }

        Championship::query()
            ->doesntHave('tires')
            ->each(function ($championship) use ($tires) {
                $championship->tires()->createMany($tires);
            });
    }
};
