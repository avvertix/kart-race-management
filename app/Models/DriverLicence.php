<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Describable;

enum DriverLicence: int implements Describable
{
    case LOCAL_NATIONAL = 10;
    case LOCAL_INTERNATIONAL = 11;
    case FOREIGN = 20;
    case EASYRACE = 30;
    case DAILY = 40;

    public function localizedName(): string
    {
        return trans("licences.driver.{$this->name}");
    }

    /**
     * @param  ?string  $provider  The name of who issues the licence, the platform-wide provider by default
     */
    public function description(?string $provider = null): string
    {
        $country = country(config('races.licence.country'));

        if ($this === DriverLicence::FOREIGN) {
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
