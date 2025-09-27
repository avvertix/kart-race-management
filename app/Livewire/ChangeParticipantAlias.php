<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Data\AliasesData;
use App\Models\Participant;
use Livewire\Component;

class ChangeParticipantAlias extends Component
{
    public Participant $participant;

    public ?string $name = null;

    public ?string $category = null;

    public ?string $bib = null;

    public bool $isEditing = false;

    public function mount()
    {
        $aliases = $this->participant->aliases;

        if ($aliases instanceof AliasesData) {
            $this->name = $aliases->name;
            $this->category = $aliases->category;
            $this->bib = $aliases->bib;
        }
    }

    public function startEditing()
    {
        $this->isEditing = true;
    }

    public function cancelEditing()
    {
        $this->isEditing = false;
    }

    public function save()
    {
        $aliases = new AliasesData(
            name: $this->name,
            category: $this->category,
            bib: $this->bib,
        );

        $this->participant->update([
            'aliases' => $aliases,
        ]);

        $this->isEditing = false;
        $this->dispatch('saved');
    }

    public function getAliasesString(): string
    {
        $aliases = new AliasesData(
            name: $this->name,
            category: $this->category,
            bib: $this->bib,
        );

        return (string) $aliases;
    }

    public function render()
    {
        return view('livewire.change-participant-alias');
    }
}
