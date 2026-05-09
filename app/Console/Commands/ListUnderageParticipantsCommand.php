<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Race;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ListUnderageParticipantsCommand extends Command
{
    protected $signature = 'race:list-underage
                            {race : The UUID of the race}
                            {category : The name or short name of the category}';

    protected $description = 'List participants under 18 years old in a given race category';

    public function handle(): int
    {
        $race = Race::where('uuid', $this->argument('race'))->first();

        if (! $race) {
            $this->error("Race [{$this->argument('race')}] not found.");

            return self::FAILURE;
        }

        $categoryTerm = $this->argument('category');

        $category = Category::where('championship_id', $race->championship_id)
            ->where(function ($query) use ($categoryTerm) {
                $query->where('name', $categoryTerm)
                    ->orWhere('short_name', $categoryTerm);
            })
            ->first();

        if (! $category) {
            $this->error("Category [{$categoryTerm}] not found in the championship for this race.");

            return self::FAILURE;
        }

        $today = Carbon::today();

        $participants = $race->participants()
            ->where('category_id', $category->getKey())
            ->get()
            ->filter(function ($participant) {
                $birthDate = $participant->driver['birth_date'] ?? null;

                if (blank($birthDate)) {
                    return false;
                }

                return Carbon::parse($birthDate)->age < 18;
            })
            ->sortBy('bib');

        if ($participants->isEmpty()) {
            $this->info("No participants under 18 found in category [{$category->name}].");

            return self::SUCCESS;
        }

        $this->info("Race: {$race->title}");
        $this->info("Category: {$category->name}");
        $this->newLine();

        $this->table(
            ['Bib', 'Name', 'Birth date', 'Age'],
            $participants->map(function ($participant) use ($today) {
                $birthDate = Carbon::parse($participant->driver['birth_date']);

                return [
                    $participant->bib,
                    "{$participant->first_name} {$participant->last_name}",
                    $birthDate->toDateString(),
                    $today->diffInYears($birthDate),
                ];
            })
        );

        return self::SUCCESS;
    }
}
