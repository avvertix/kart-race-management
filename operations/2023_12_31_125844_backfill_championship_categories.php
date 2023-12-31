<?php

use App\Models\Championship;
use App\Models\ChampionshipTire;
use Illuminate\Support\Facades\Storage;
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

        $categories = collect(config('categories.default'))
            ->merge(json_decode(Storage::disk(config('categories.disk'))->get(config('categories.file')) ?? '{}', true))
            ->map(function($value, $key){
                return [
                    'code' => $key,
                    ...$value,
                    'short_name' => $value['timekeeper_label'] ?? null,
                ];
            })->filter();

        if($categories->isEmpty()){
            return;
        }

        Championship::query()
            ->has('tires')
            ->doesntHave('categories')
            ->each(function($championship) use ($categories){

                $categoriesToCreate = $categories->map(function($value) use ($championship){
                    $tire = $championship->tires()->whereCode($value['tires'])->first();
                    
                    if(is_null($tire)){
                        return null;
                    }

                    return [
                        ...$value,
                        'championship_tire_id' => $tire?->getKey(),
                    ];
                })->filter();

                if($categoriesToCreate->count() !== $categories->count()){
                    logs()->warning("Categories not imported in championship [{$championship->getKey()}]. Missing tires.");
                    return;
                }

                $championship->categories()->createMany($categoriesToCreate);
            });
    }
};
