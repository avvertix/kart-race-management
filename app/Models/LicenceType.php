<?php

namespace App\Models;

enum LicenceType: int
{
    case LOCAL_CLUB = 10;
    case LOCAL_NATIONAL = 20;
    case LOCAL_INTERNATIONAL = 30;
    case FOREIGN = 40;
    case MECHANIC = 50;
    case COMPETITOR = 60;
}
