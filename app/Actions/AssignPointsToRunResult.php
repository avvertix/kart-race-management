<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\WildcardPointsMode;
use App\Models\ChampionshipPointScheme;
use App\Models\RunResult;
use Illuminate\Support\Collection;

class AssignPointsToRunResult
{
    public function __invoke(RunResult $runResult, ChampionshipPointScheme $pointScheme): void
    {
        $runResult->loadMissing('race');

        $race = $runResult->race;
        $runType = $runResult->run_type;
        $config = $pointScheme->points_config;

        $participantResults = $runResult->participantResults()->with('participant')->get();

        $categoryCounts = $participantResults->countBy('category');

        $needsReranking = $config->wildcardPointsMode !== WildcardPointsMode::AsOtherDrivers;
        $rankedPositions = $needsReranking
            ? $this->computeRankedFromFirstPositions($participantResults)
            : [];

        foreach ($participantResults as $participantResult) {
            $position = (int) $participantResult->position_in_category;
            $isWildcard = (bool) ($participantResult->participant?->wildcard);

            if ($participantResult->status->unfinishedOrPenalty()) {
                $points = $config->getPointsForStatus($runType, $participantResult->status, $position);
            } elseif ($isWildcard && $config->wildcardPointsMode === WildcardPointsMode::FixedPoints) {
                $points = $config->wildcardFixedPoints;
            } elseif ($needsReranking) {
                $rankedPosition = $rankedPositions[$participantResult->getKey()] ?? $position;
                $points = $config->getPointsForPosition($runType, $rankedPosition);
            } else {
                $points = $config->getPointsForPosition($runType, $position);
            }

            $categoryCount = $categoryCounts->get($participantResult->category, 0);

            if ($categoryCount < $config->smallCategoryThreshold && $categoryCount > 0) {
                $points = $points * (1 + ($config->smallCategoryPercentage / 100));
            }

            if ($race->rain) {
                $points = $points * (1 - ($config->rainPercentage / 100));
            }

            if ($race->red_flag) {
                $points = $points * (1 - ($config->redFlagPercentage / 100));
            }

            if ($race->point_multiplier) {
                $points = $points * $race->point_multiplier;
            }

            $points = round($points, 2);

            $participantResult->update(['points' => $points]);
        }
    }

    /**
     * Compute per-result ranked positions for RankedFromFirst mode.
     * Within each category, wildcards and non-wildcards are ranked independently from 1.
     *
     * @param  Collection<int, \App\Models\ParticipantResult>  $participantResults
     * @return array<mixed, int>
     */
    private function computeRankedFromFirstPositions(Collection $participantResults): array
    {
        $positions = [];

        $participantResults
            ->filter(fn ($r) => ! $r->status->unfinishedOrPenalty())
            ->groupBy('category')
            ->each(function (Collection $categoryResults) use (&$positions): void {
                [$wildcards, $nonWildcards] = $categoryResults->partition(fn ($r) => (bool) ($r->participant?->wildcard));

                foreach ([$wildcards, $nonWildcards] as $group) {
                    $group->sortBy(fn ($r) => (int) $r->position_in_category)
                        ->values()
                        ->each(function ($result, int $index) use (&$positions): void {
                            $positions[$result->getKey()] = $index + 1;
                        });
                }
            });

        return $positions;
    }
}
