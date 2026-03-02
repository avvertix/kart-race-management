<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AwardRankingMode;
use App\Models\AwardType;
use App\Models\Category;
use App\Models\Championship;
use App\Models\WildcardFilter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ChampionshipAward>
 */
class ChampionshipAwardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'championship_id' => Championship::factory(),
            'name' => fake()->word(),
            'type' => AwardType::Category,
            'ranking_mode' => AwardRankingMode::All,
            'wildcard_filter' => WildcardFilter::All,
            'category_id' => Category::factory(),
        ];
    }

    public function categoryAward(?Category $category = null): static
    {
        return $this->state(fn () => [
            'type' => AwardType::Category,
            'category_id' => $category?->getKey() ?? Category::factory(),
        ]);
    }

    public function overallAward(): static
    {
        return $this->state(fn () => [
            'type' => AwardType::Overall,
            'ranking_mode' => AwardRankingMode::All,
            'category_id' => null,
        ]);
    }

    public function bestN(int $n): static
    {
        return $this->state(fn () => [
            'ranking_mode' => AwardRankingMode::BestN,
            'best_n' => $n,
        ]);
    }

    public function specificRaces(): static
    {
        return $this->state(fn () => [
            'ranking_mode' => AwardRankingMode::SpecificRaces,
        ]);
    }

    public function onlyWildcards(): static
    {
        return $this->state(fn () => [
            'wildcard_filter' => WildcardFilter::OnlyWildcards,
        ]);
    }

    public function excludeWildcards(): static
    {
        return $this->state(fn () => [
            'wildcard_filter' => WildcardFilter::ExcludeWildcards,
        ]);
    }
}
