<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\AssignPointsToRunResult;
use App\Models\ChampionshipPointScheme;
use App\Models\Race;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AssignPointsToRaceResults implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Race $race,
        public ChampionshipPointScheme $pointScheme,
    ) {}

    public function handle(AssignPointsToRunResult $assignPoints): void
    {
        $runResults = $this->race->results()->get();

        foreach ($runResults as $runResult) {
            $assignPoints($runResult, $this->pointScheme);
        }
    }
}
