<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ChampionshipPointScheme;
use App\Models\RunResult;

class AssignPointsToRunResult
{
    public function __invoke(RunResult $runResult, ChampionshipPointScheme $pointScheme): void
    {
        $runResult->loadMissing('race');

        $race = $runResult->race;
        $runType = $runResult->run_type;
        $config = $pointScheme->points_config;

        $participantResults = $runResult->participantResults()->get();

        $categoryCounts = $participantResults->countBy('category');

        foreach ($participantResults as $participantResult) {
            $position = (int) $participantResult->position_in_category;

            if ($participantResult->status->unfinishedOrPenalty()) {
                $points = $config->getPointsForStatus($runType, $participantResult->status, $position);
            } else {
                $points = $config->getPointsForPosition($runType, $position);
            }

            $categoryCount = $categoryCounts->get($participantResult->category, 0);

            if ($categoryCount <= $config->smallCategoryThreshold && $categoryCount > 0) {
                $points = $points * (1 + ($config->smallCategoryPercentage / 100));
            }

            if ($race->rain) {
                $points = $points * (1 - ($config->rainPercentage / 100));
            }

            if ($race->point_multiplier) {
                $points = $points * $race->point_multiplier;
            }

            $points = round($points, 2);

            $participantResult->update(['points' => $points]);
        }
    }
}
