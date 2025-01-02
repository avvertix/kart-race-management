<?php

declare(strict_types=1);

namespace Tests;

trait CreateVehicle
{
    protected function generateValidVehicle()
    {
        return [
            'vehicle_chassis_manufacturer' => 'Chassis',
            'vehicle_engine_manufacturer' => 'Engine Manufacturer',
            'vehicle_engine_model' => 'Engine Model',
            'vehicle_oil_manufacturer' => 'Oil Manufacturer',
            'vehicle_oil_type' => 'Oil Type',
            'vehicle_oil_percentage' => '4',
        ];
    }
}
