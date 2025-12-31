<?php

declare(strict_types=1);

use App\Models\Race;
use App\Models\RaceType;
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
        // Migrate REGIONAL (20) and ZONE (30) race types to NATIONAL (40)
        Race::query()
            ->whereIn('type', [20, 30]) // REGIONAL = 20, ZONE = 30
            ->update(['type' => RaceType::NATIONAL]);
    }
};
