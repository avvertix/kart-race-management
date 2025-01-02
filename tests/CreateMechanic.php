<?php

declare(strict_types=1);

namespace Tests;

trait CreateMechanic
{
    protected function generateValidMechanic()
    {
        return [
            'mechanic_name' => 'Mechanic Racer',
            'mechanic_licence_number' => 'M0003',
        ];
    }
}
