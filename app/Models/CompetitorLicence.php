<?php

declare(strict_types=1);

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

    /**
     * @param  ?string  $provider  The name of who issues the licence, the platform-wide provider by default
     */
    public function description(?string $provider = null): string
    {
        $country = country(config('races.licence.country'));

        if ($this === CompetitorLicence::FOREIGN) {
            return __('Licence issued out of :country', [
                'country' => $country->getName(),
            ]);
        }

        return __('Licence issued by :provider in :country', [
            'provider' => $provider ?? config('races.licence.provider'),
            'country' => $country->getName(),
        ]);
    }
}
