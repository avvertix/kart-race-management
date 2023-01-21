<?php

namespace App\Models;

use App\Support\Describable;

enum DriverLicence: int implements Describable
{
    case LOCAL_CLUB = 10;
    case LOCAL_NATIONAL = 11;
    case LOCAL_INTERNATIONAL = 12;
    case FOREIGN = 20;


    public function description(): string
    {
        return 'test';
    }
}
