<?php

declare(strict_types=1);

use App\Models\Championship;
use App\Models\WildcardStrategy;
use TimoKoerber\LaravelOneTimeOperations\OneTimeOperation;

return new class extends OneTimeOperation
{
    /**
     * Determine if the operation is being processed asynchronously.
     */
    protected bool $async = true;

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
        Championship::query()
            ->lazy()
            ->each(function ($championship) {

                if ($championship->wildcard->strategy === WildcardStrategy::BASED_ON_FIRST_RACE) {
                    $championship->wildcard->enabled = false;
                    $championship->wildcard->strategy = null;
                    $championship->save();
                }

            });
    }
};
