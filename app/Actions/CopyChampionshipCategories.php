<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Category;
use App\Models\Championship;
use App\Models\ChampionshipTire;
use Illuminate\Support\Collection;

class CopyChampionshipCategories
{
    /**
     * Copy championship categories from a source championship to a target championship.
     *
     * This action will copy all categories and automatically create any missing tires
     * in the target championship if they don't already exist.
     *
     * @param  Championship  $sourceChampionship  The championship to copy categories from
     * @param  Championship  $targetChampionship  The championship to copy categories to
     * @return Collection<int, Category> The newly created categories
     */
    public function __invoke(Championship $sourceChampionship, Championship $targetChampionship): Collection
    {
        $sourceCategories = $sourceChampionship->categories;

        // Cache target tires by code for efficient lookups
        $targetTiresByCode = $targetChampionship->tires->keyBy('code');

        $copiedCategories = $sourceCategories->map(function (Category $category) use ($targetChampionship, $targetTiresByCode) {
            $targetTireId = null;

            // If the category has a tire, ensure it exists in the target championship
            if ($category->championship_tire_id && $category->tire) {
                $sourceTire = $category->tire;

                // Check if tire already exists in target championship
                $targetTire = $targetTiresByCode->get($sourceTire->code);

                // If tire doesn't exist, create it
                if (! $targetTire) {
                    $targetTire = $sourceTire->replicate();

                    $targetChampionship->tires()->save($targetTire);

                    // Add to cache for future categories
                    $targetTiresByCode->put($targetTire->code, $targetTire);
                }

                $targetTireId = $targetTire->id;
            }

            $copiedCategory = $category->replicate()->fill([
                'championship_tire_id' => $targetTireId,
            ]);

            $targetChampionship->categories()->save($copiedCategory);

            return $copiedCategory;
        });

        return $copiedCategories;
    }
}
