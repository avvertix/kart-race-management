<?php

use App\Models\Participant;
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
        Participant::query()
            ->with('championship')
            ->has('championship.categories')
            ->whereNull('category_id')
            ->each(function($participant) {

                $category = $participant->championship->categories()->whereCode($participant->category)->first();

                if(is_null($category)){
                    return;
                }

                $participant->category_id = $category->getKey();

                $participant->saveQuietly();
            });
    }
};
