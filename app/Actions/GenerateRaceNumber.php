<?php

namespace App\Actions;

use App\Models\Championship;
use App\Models\Race;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use App\Models\Sex;
use App\Rules\ExistsCategory;
use App\Models\DriverLicence;
use App\Models\Participant;
use App\Models\CompetitorLicence;
use App\Models\TrashedParticipant;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GenerateRaceNumber
{

    /**
     * Generate a set of race numbers based on available ones within the championship
     *
     * @param  \App\Models\Championship  $championship
     * @param  int  $count How many suggestions to generate
     * @return array|\Illuminate\Support\Collection
     */
    public function __invoke(Championship $championship, $count = 1)
    {
        $existing = Participant::where('championship_id', $championship->getKey())->distinct()->get('bib')->pluck('bib');

        $range = collect()->range(1, $existing->max() + 10)->diff($existing)->random(5);

        return $range->toArray();
    }

    
    
}
