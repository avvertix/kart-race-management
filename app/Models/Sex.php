<?php

namespace App\Models;

enum Sex: int
{
    case MALE = 10;
    case FEMALE = 20;
    case UNSPECIFIED = 30;
}
