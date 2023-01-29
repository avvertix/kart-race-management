<?php

namespace App\Models;

use App\Support\Describable;
use Illuminate\Support\Str;

enum CompetitorLicence: int implements Describable
{
    case LOCAL = 10;
    case FOREIGN = 40;

    public function localizedName(): string
    {
        return Str::title($this->name);
    }

    public function description(): string
    {
        $country = country(config('races.licence.country'));

        if($this == CompetitorLicence::FOREIGN){
            return __('Licence issued out of :country', [
                'country' => $country->getName(),
            ]);
        }

        return __('Licence issued by :provider in :country', [
            'provider' => config('races.licence.provider'),
            'country' => $country->getName(),
        ]);
    }
}
