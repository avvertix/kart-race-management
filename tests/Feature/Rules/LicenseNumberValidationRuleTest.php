<?php

declare(strict_types=1);

namespace Tests\Feature\Rules;

use App\Rules\LicenseNumberValidationRule;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LicenseNumberValidationRuleTest extends TestCase
{
    public static function acceptable_license_numbers_provider()
    {
        return [
            ['1234'],
            ['456789'],
            ['65498721'],
            ['ASI(123456)'],
            ['NK-J-022'],
            ['K/Junior/1013'],
            ['INT K E 510483'],
            ['KD.28.0012.50'],
        ];
    }

    public static function invalid_license_numbers_provider()
    {
        return [
            [null],
            [false],
            [true],
            ['true'],
            ['false'],
            ['null'],
            ['DSQ'],
            ['DQ'],
            ['DNF'],
            ['asi'],
            ['aci'],
            ['DNS'],
            ['///'],
            ['?'],
            ['in attesa'],
            ['dovete darmela voi'],
            ['dnf'],
            ['dns'],
            ['finished'],
            ['any other text'],
            ['0000'],
            ['0000000'],
            ['vll'],
            ['.'],
            ['-'],
            ['Wuey wiwi'],
            ['m'],
            ['France'],
            ['Svizzera'],
            ['XXXX'],
            ['Xxx'],
            ['AT'],
            ['Austria'],
            ['Xtx'],
            ['Ttt'],
            ['xy'],
            ['CHE'],
            ['/'],
            ['1'],
            ['Xxxxxxxx'],
            ['?'],
            ['Sweden'],
            ['Spagan'],
            ['Aut.'],
            ['A'],
            ['.........................................'],
            ['...........................'],
            ['Pppppppppp'],
            ['////'],
            ['//'],
            ['s'],
            ['Fr'],
            [';'],
            ['…………..'],
            ['NA'],
            ['N/A'],
            ['NA'],
            ['///'],
        ];
    }

    #[DataProvider('acceptable_license_numbers_provider')]
    public function test_acceptable_license_numbers_recognized($license_number): void
    {
        $validator = Validator::make([
            'license_number' => $license_number,
        ], [
            'license_number' => new LicenseNumberValidationRule(),
        ]);

        $this->assertFalse($validator->fails());
    }

    #[DataProvider('invalid_license_numbers_provider')]
    public function test_invalid_license_numbers($license_number): void
    {
        $validator = Validator::make([
            'license_number' => $license_number,
        ], [
            'license_number' => new LicenseNumberValidationRule(),
        ]);

        $this->assertTrue($validator->fails());
    }
}
