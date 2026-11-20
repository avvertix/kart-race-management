<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\WildcardPointsMode;
use App\Models\ChampionshipPointScheme;
use App\Models\ParticipantResult;
use App\Models\RunResult;
use Illuminate\Support\Collection;

class AssignPointsToRunResult
{
    public function __invoke(RunResult $runResult, ChampionshipPointScheme $pointScheme): void
    {
        if (! $runResult->isScored()) {
            return;
        }

        $runResult->loadMissing('race');

        $race = $runResult->race;
        $runType = $runResult->scoringRunType();
        $config = $pointScheme->points_config;

        $participantResults = $runResult->participantResults()->with('participant')->get();

        $categoryCounts = $this->categoryCounts($runResult, $participantResults);

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

            $modifierPercentage = 0;

            if ($categoryCount < $config->smallCategoryThreshold && $categoryCount > 0) {
                $modifierPercentage += $config->smallCategoryPercentage;
            }

            if ($race->rain) {
                $modifierPercentage += $config->rainPercentage;
            }

            $points = max(0, $points * (1 + ($modifierPercentage / 100)));

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
     * Number of drivers of each category in the session. A category split across the heats
     * promoted to the same race is counted across all of them, as they form a single race.
     *
     * @param  Collection<int, ParticipantResult>  $participantResults
     * @return Collection<string, int>
     */
    private function categoryCounts(RunResult $runResult, Collection $participantResults): Collection
    {
        if (! $runResult->isPromoted()) {
            return $participantResults->countBy('category');
        }

        return ParticipantResult::query()
            ->whereHas('runResult', fn ($query) => $query
                ->where('race_id', $runResult->race_id)
                ->where('run_type', $runResult->run_type->value)
                ->where('promoted_to', $runResult->promoted_to->value))
            ->pluck('category')
            ->countBy();
    }

    /**
     * Compute per-result ranked positions for RankedFromFirst mode.
     * Within each category, wildcards and non-wildcards are ranked independently from 1.
     *
     * @param  Collection<int, ParticipantResult>  $participantResults
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
