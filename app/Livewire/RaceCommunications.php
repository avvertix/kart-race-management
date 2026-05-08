<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\CommunicationType;
use App\Models\Race;
use App\Models\RaceCommunication;
use App\Models\RunType;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class RaceCommunications extends Component
{
    #[Locked]
    public int|string $race_id;

    public string $type = 'penalty';

    public ?int $run_type = null;

    public string $message = '';

    public function mount(Race $race): void
    {
        $this->race_id = $race->getKey();
    }

    #[Computed]
    public function race(): Race
    {
        return Race::with('championship')->findOrFail($this->race_id);
    }

    #[Computed]
    public function penalties(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->race->championship->penalties()->get();
    }

    #[Computed]
    public function communications(): \Illuminate\Database\Eloquent\Collection
    {
        return RaceCommunication::where('race_id', $this->race_id)
            ->with('user')
            ->latest()
            ->get();
    }

    #[Computed]
    public function runTypes(): array
    {
        return RunType::cases();
    }

    #[Computed]
    public function types(): array
    {
        return CommunicationType::cases();
    }

    public function post(): void
    {
        $this->authorize('create', RaceCommunication::class);

        $this->validate([
            'type' => ['required', 'string', 'in:communication,penalty'],
            'run_type' => ['nullable', 'integer', 'in:'.implode(',', array_column(RunType::cases(), 'value'))],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        RaceCommunication::create([
            'race_id' => $this->race_id,
            'championship_id' => $this->race->championship_id,
            'user_id' => Auth::id(),
            'type' => $this->type,
            'run_type' => $this->run_type,
            'message' => $this->message,
        ]);

        $this->reset(['message', 'run_type']);
        $this->type = 'communication';

        unset($this->communications);

        $this->dispatch('posted');
    }

    public function toggleRead(string $ulid): void
    {
        $this->authorize('update', RaceCommunication::class);

        $communication = RaceCommunication::where('ulid', $ulid)->firstOrFail();

        $communication->read_at = $communication->read_at ? null : now();
        $communication->save();

        unset($this->communications);
    }

    public function delete(string $ulid): void
    {
        $communication = RaceCommunication::where('ulid', $ulid)->firstOrFail();

        $this->authorize('delete', $communication);

        $communication->delete();

        unset($this->communications);
    }

    public function render()
    {
        return view('livewire.race-communications');
    }
}
