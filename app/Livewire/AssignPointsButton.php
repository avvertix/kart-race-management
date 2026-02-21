<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\AssignPointsToRunResult;
use App\Jobs\AssignPointsToRaceResults;
use App\Models\ChampionshipPointScheme;
use App\Models\Race;
use App\Models\RunResult;
use Livewire\Component;

class AssignPointsButton extends Component
{
    public Race $race;

    public ?int $runResultId = null;

    public bool $showModal = false;

    public string $selectedPointScheme = '';

    /** @var array<int, array{id: int, name: string}> */
    public array $pointSchemes = [];

    public function mount(Race $race, ?RunResult $runResult = null): void
    {
        $this->race = $race;
        $this->runResultId = $runResult?->getKey();
    }

    public function openAssignPoints(): void
    {
        $schemes = ChampionshipPointScheme::query()
            ->where('championship_id', $this->race->championship_id)
            ->get(['id', 'name']);

        if ($schemes->isEmpty()) {
            session()->flash('flash.banner', __('No point schemes configured for this championship.'));
            session()->flash('flash.bannerStyle', 'danger');

            return;
        }

        if ($schemes->count() === 1) {
            $this->selectedPointScheme = (string) $schemes->first()->getKey();
            $this->assignPoints();

            return;
        }

        $this->pointSchemes = $schemes->map(fn ($s) => ['id' => $s->getKey(), 'name' => $s->name])->all();
        $this->showModal = true;
    }

    public function assignPoints(): void
    {
        if (empty($this->selectedPointScheme)) {
            return;
        }

        $pointScheme = ChampionshipPointScheme::findOrFail((int) $this->selectedPointScheme);

        if ($this->runResultId) {
            $runResult = RunResult::findOrFail($this->runResultId);
            app(AssignPointsToRunResult::class)($runResult, $pointScheme);
            $message = __('Points assigned.');
        } else {
            AssignPointsToRaceResults::dispatch($this->race, $pointScheme);
            $message = __('Points assignment queued for all results.');
        }

        $this->showModal = false;
        $this->selectedPointScheme = '';

        session()->flash('flash.banner', $message);

        $this->redirect(
            $this->runResultId
                ? route('results.show', RunResult::findOrFail($this->runResultId))
                : route('races.results.index', $this->race),
            navigate: false
        );
    }

    public function render()
    {
        return view('livewire.assign-points-button');
    }
}
