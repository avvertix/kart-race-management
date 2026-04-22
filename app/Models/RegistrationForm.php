<?php

declare(strict_types=1);

namespace App\Models;

enum RegistrationForm: string
{
    case Complete = 'complete';
    case Minimal = 'minimal';
}
