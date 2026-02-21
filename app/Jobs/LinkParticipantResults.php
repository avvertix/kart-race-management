<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Participant;
use App\Models\RunResult;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class LinkParticipantResults implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public RunResult $runResult,
    ) {}

    public function handle(): void
    {
        $unlinkedResults = $this->runResult->participantResults()
            ->whereNull('participant_id')
            ->get();

        if ($unlinkedResults->isEmpty()) {
            return;
        }

        $racerHashes = $unlinkedResults->pluck('racer_hash')->unique()->filter()->values();

        $participants = Participant::query()
            ->where('race_id', $this->runResult->race_id)
            ->whereIn('racer_hash', $racerHashes)
            ->get()
            ->keyBy('racer_hash');

        $unlinkedResults->each(function ($participantResult) use ($participants) {
            $participant = $participants->get($participantResult->racer_hash);

            if ($participant === null) {
                return;
            }

            $participantResult->update([
                'participant_id' => $participant->id,
                'category_id' => $participant->category_id,
            ]);
        });
    }
}
