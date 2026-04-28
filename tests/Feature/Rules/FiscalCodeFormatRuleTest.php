<?php

declare(strict_types=1);

namespace Tests\Feature\Rules;

use App\Models\CompetitorLicence;
use App\Models\DriverLicence;
use App\Rules\FiscalCodeFormatRule;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FiscalCodeFormatRuleTest extends TestCase
{
    public static function valid_fiscal_codes_provider()
    {
        return [
            ['RSSMRA85T10A562S'],
            ['VRNGPP80A01H501H'],
            ['rssmra85t10a562s'],
            ['rssmraurtmlarsnl'],
        ];
    }

    public static function invalid_fiscal_codes_provider()
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
            ['DNS'],
            ['FINISHED'],
            ['dsq'],
            ['dq'],
            ['dnf'],
            ['dns'],
            ['finished'],
            ['any other text'],
            [fake('fr_FR')->nir()],
            [fake('es_VE')->nationalId()],
            ['201303154072'],
            ['0000'],
            ['vll'],
            ['.'],
            ['-'],
            ['Wuey wiwi'],
            ['m'],
            ['France'],
            ['Svizzera'],
            ['321654'],
            ['1610217400551'],
            ['8308 Illnau'],
            ['XXXX'],
            ['JFVJSBDFVSD'],
            ['KNFVSDFN'],
            ['ATU78911723'],
            ['Xxx'],
            ['AT'],
            ['Austria'],
            ['9812'],
            ['5464'],
            ["in svizzera non c'e"],
            ['1462'],
            ['51402260083'],
            ['1474'],
            ['1239990000'],
            ['KSJDHFBAHJBDV'],
            ['KJAHFADFV'],
            ['Xx'],
            ['Ttt'],
            ['171214'],
            ['28.0003.25'],
            ['020609'],
            ['1111'],
            ['1234'],
            ['50112100889'],
            ['xy'],
            ['CHE'],
            ['03022012'],
            ['/'],
            ['98000 Monaco'],
            ['1'],
            ['CNIC LS13M25L219F'],
            ['28.0034.25'],
            ['ASS-K-NAT-E'],
            ['8308'],
            ['505146'],
            ['NKAFNANFVA'],
            ['361860'],
            ['6403'],
            ['500028'],
            ['0111'],
            ['KJAFVHDFBV'],
            ['ESTERO'],
            ['CH'],
            ['100227/2557'],
            ['Emlkam08h286n'],
            ['Xxxxxxxx'],
            ['?'],
            ['27041788580'],
            ['Sweden'],
            ['AA5946516'],
            ['320067'],
            ['Spagan'],
            ['Aut.'],
            ['A'],
            ['.........................................'],
            ['...........................'],
            ['6812'],
            ['51305070024'],
            ['0708106410'],
            ['Pppppppppp'],
            ['////'],
            ['1002272557111111'],
            ['//'],
            ['s'],
            ['Fr'],
            [';'],
            ['…………..'],
            ['7141'],
            ['NA'],
            ['///'],
            ['N/A'],
            ['72 297/2540'],
            ['0000000'],
            ['0000000000000000'],
        ];
    }

    #[DataProvider('valid_fiscal_codes_provider')]
    public function test_valid_fiscal_code_recognized($fiscal_code): void
    {
        $validator = Validator::make([
            'fiscal_code' => $fiscal_code,
        ], [
            'fiscal_code' => new FiscalCodeFormatRule(),
        ]);

        $this->assertFalse($validator->fails());
    }

    #[DataProvider('invalid_fiscal_codes_provider')]
    public function test_invalid_fiscal_codes($fiscal_code): void
    {
        $validator = Validator::make([
            'fiscal_code' => $fiscal_code,
        ], [
            'fiscal_code' => new FiscalCodeFormatRule(),
        ]);

        $this->assertTrue($validator->fails());
    }

    public function test_fiscal_code_required_when_driver_nationality_is_italian(): void
    {
        $validator = Validator::make([
            'fiscal_code' => null,
            'driver_nationality' => 'Italia',
        ], [
            'fiscal_code' => new FiscalCodeFormatRule(check_driver: true),
        ]);

        $this->assertTrue($validator->fails());
    }

    public function test_fiscal_code_required_when_driver_licence_is_not_foreign(): void
    {
        $validator = Validator::make([
            'fiscal_code' => null,
            'driver_nationality' => '',
            'driver_licence_type' => DriverLicence::LOCAL_NATIONAL->value,
        ], [
            'fiscal_code' => new FiscalCodeFormatRule(check_driver: true),
        ]);

        $this->assertTrue($validator->fails());
    }

    public function test_fiscal_code_required_when_competitor_nationality_is_italian(): void
    {
        $validator = Validator::make([
            'fiscal_code' => null,
            'competitor_nationality' => 'Italia',
        ], [
            'fiscal_code' => new FiscalCodeFormatRule(check_competitor: true),
        ]);

        $this->assertTrue($validator->fails());
    }

    public function test_fiscal_code_required_when_competitor_licence_is_not_foreign(): void
    {
        $validator = Validator::make([
            'fiscal_code' => null,
            'competitor_nationality' => '',
            'competitor_licence_type' => CompetitorLicence::LOCAL->value,
        ], [
            'fiscal_code' => new FiscalCodeFormatRule(check_competitor: true),
        ]);

        $this->assertTrue($validator->fails());
    }
}
