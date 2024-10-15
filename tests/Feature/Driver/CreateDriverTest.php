<?php

namespace Tests\Feature\Driver;

use App\Actions\Driver\CreateDriver as CreateDriverAction;
use App\Data\AddressData;
use App\Data\BirthData;
use App\Data\LicenceData;
use App\Models\Championship;
use App\Models\Driver;
use App\Models\DriverLicence;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Validation\ValidationException;
use Tests\CreateDriver;
use Tests\TestCase;

class CreateDriverTest extends TestCase
{
    use RefreshDatabase;

    use CreateDriver;

    
    public function test_driver_created(): void
    {
        $championship = Championship::factory()->create();

        $addDriver = app()->make(CreateDriverAction::class);

        $driver = $addDriver($championship, [
            'bib' => 100,
            ...$this->generateValidDriver(),
        ]);

        $expectedLicenceHash = hash('sha512', 'D0001');

        $this->assertInstanceOf(Driver::class, $driver);

        $this->assertEquals(substr($expectedLicenceHash, 0, 8), $driver->code);

        $this->assertEquals('john@racer.local', $driver->email);
        $this->assertEquals('555555555', $driver->phone);
        $this->assertEquals('John', $driver->first_name);
        $this->assertEquals('Racer', $driver->last_name);
        $this->assertEquals('DRV-FC', $driver->fiscal_code);

        $this->assertInstanceOf(LicenceData::class, $driver->licence);
        $this->assertEquals('D0001', $driver->licence_number);
        $this->assertEquals($expectedLicenceHash, $driver->licence_hash);
        $this->assertEquals(DriverLicence::LOCAL_NATIONAL, $driver->licence_type);
        
        $this->assertEquals(today()->addYear(), $driver->medical_certificate_expiration_date);

        $this->assertNull($driver->user_id);
        $this->assertTrue($driver->championship->is($championship));

        $this->assertInstanceOf(BirthData::class, $driver->birth);
        $this->assertEquals(hash('sha512', '1999-11-11'), $driver->birth_date_hash);
        $this->assertEquals(Carbon::parse('1999-11-11'), $driver->birth->date);
        $this->assertEquals('Milan', $driver->birth->place);

        $this->assertInstanceOf(AddressData::class, $driver->address);
        $this->assertEquals('via dei Platani, 40', $driver->address->address);
        $this->assertEquals('Milan', $driver->address->city);
        $this->assertEquals('Milan', $driver->address->province);
        $this->assertEquals('20146', $driver->address->postal_code);
    }

    public function test_driver_not_created_when_validation_fails(): void
    {
        $championship = Championship::factory()->create();

        $addDriver = app()->make(CreateDriverAction::class);

        $this->expectException(ValidationException::class);

        $driver = $addDriver($championship, [
            'bib' => 100,
            ...$this->generateValidDriver(['driver_licence_number']),
        ]);

        $this->assertDatabaseEmpty(Driver::class);
    }

    public function test_driver_not_created_when_already_within_championship(): void
    {
        $championship = Championship::factory()
            ->has(Driver::factory()
                ->state([
                    'licence_number' => 'D0001',
                    'licence_hash' => hash('sha512', 'D0001')
                ]), 'drivers')
            ->create();

        $addDriver = app()->make(CreateDriverAction::class);

        $this->expectException(ValidationException::class);

        $driver = $addDriver($championship, [
            'bib' => 100,
            ...$this->generateValidDriver(),
        ]);

        $this->assertDatabaseCount(Driver::class, 1);
    }
}
