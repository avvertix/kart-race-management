<?php

namespace App\Actions;

use App\Models\BibReservation;
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

    private const HIGHEST_SUGGESTED_RACE_NUMBER = 999;

    /**
     * Generate a set of race numbers based on available ones within the championship
     *
     * @param  \App\Models\Championship  $championship
     * @param  int  $count How many suggestions to generate
     * @return array|\Illuminate\Support\Collection
     */
    public function __invoke(Championship $championship, $count = 4)
    {
        $count = abs($count);

        $existing = Participant::where('championship_id', $championship->getKey())->distinct()->select('bib')->pluck('bib');

        $reserved = BibReservation::where('championship_id', $championship->getKey())->distinct()->select('bib')->pluck('bib');

        $max = max($existing->max(), self::HIGHEST_SUGGESTED_RACE_NUMBER);

        $options = collect()->range(1, $max == 0 ? 100 : $max)->diff($existing)->diff($reserved);

        $range = $options->count() > $count ? $options->take($count) : $options;

        return $range->values()->toArray();
    }

    
    
}
