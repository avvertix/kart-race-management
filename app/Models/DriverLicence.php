<?php

namespace App\Models;

use App\Support\Describable;

enum DriverLicence: int implements Describable
{
    case LOCAL_NATIONAL = 10;
    case LOCAL_INTERNATIONAL = 11;
    case FOREIGN = 20;


    public function localizedName(): string
    {
        return trans("licences.driver.{$this->name}");
    }


    public function description(): string
    {
        $country = country(config('races.licence.country'));

        if($this == DriverLicence::FOREIGN){
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
