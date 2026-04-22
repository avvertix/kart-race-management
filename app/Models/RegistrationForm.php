<?php

declare(strict_types=1);

namespace App\Models;

enum RegistrationForm: string
{
    case Complete = 'complete';
    case Standard = 'standard';
    case Minimal = 'minimal';

    public static function resolve(?Race $race): self
    {
        $raceForm = $race?->registration_form;
        if ($raceForm !== null) {
            return $raceForm;
        }

        $championshipForm = $race?->championship?->registration_form;
        if ($championshipForm !== null) {
            return $championshipForm;
        }

        return self::tryFrom(config('races.registration.form')) ?? self::Complete;
    }
}
