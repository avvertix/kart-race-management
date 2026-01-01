<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Championship;
use App\Models\ChampionshipTire;
use Illuminate\Support\Collection;

class CopyChampionshipTires
{
    /**
     * Copy championship tires from a source championship to a target championship.
     *
     * @param  Championship  $sourceChampionship  The championship to copy tires from
     * @param  Championship  $targetChampionship  The championship to copy tires to
     * @return Collection<int, ChampionshipTire> The newly created championship tires
     */
    public function __invoke(Championship $sourceChampionship, Championship $targetChampionship): Collection
    {
        $sourceTires = $sourceChampionship->tires;

        $copiedTires = $sourceTires->map(function (ChampionshipTire $tire) use ($targetChampionship) {
            $copiedTire = $tire->replicate();

            $targetChampionship->tires()->save($copiedTire);

            return $copiedTire;
        });

        return $copiedTires;
    }
}
