<?php

declare(strict_types=1);

namespace Tests;

trait CreateVehicle
{
    protected function generateValidVehicle()
    {
        return [
            'vehicle_chassis_manufacturer' => 'Chassis',
            'vehicle_chassis_model' => 'Chassis Model',
            'vehicle_chassis_homologation' => 'CM12345',
            'vehicle_chassis_number' => 'CN67890',
            'vehicle_engine_manufacturer' => 'Engine Manufacturer',
            'vehicle_engine_model' => 'Engine Model',
            'vehicle_engine_homologation' => 'OM12345',
            'vehicle_engine_number' => 'EN67890',
            'vehicle_oil_manufacturer' => 'Oil Manufacturer',
            'vehicle_oil_type' => 'Oil Type',
            'vehicle_oil_percentage' => '4',
        ];
    }
}
