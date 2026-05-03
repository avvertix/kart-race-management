<?php

declare(strict_types=1);

namespace App;

enum ParticipantStatus: string
{
    case Draft = 'draft';
    case Registered = 'registered';
}
