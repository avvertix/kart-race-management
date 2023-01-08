<?php

namespace App\Models;

enum DriverLicence: int
{
    case LOCAL_CLUB = 10;
    case LOCAL_NATIONAL = 11;
    case LOCAL_INTERNATIONAL = 12;
    case FOREIGN = 20;
}
