<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\CompetitorLicence;
use App\Models\DriverLicence;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;
use InvalidArgumentException;

class FiscalCodeFormatRule implements DataAwareRule, ValidationRule
{
    // women char
    protected const CHR_WOMEN = 'F';

    // male char
    protected const CHR_MALE = 'M';

    /**
     * All of the data under validation.
     *
     * @var array<string, mixed>
     */
    protected $data = [];

    /**
     * Array of all available months.
     */
    protected $months = [
        '1' => 'A',
        '2' => 'B',
        '3' => 'C',
        '4' => 'D',
        '5' => 'E',
        '6' => 'H',
        '7' => 'L',
        '8' => 'M',
        '9' => 'P',
        '10' => 'R',
        '11' => 'S',
        '12' => 'T',
    ];

    /**
     * Array of all available odd characters.
     */
    protected $odd = [
        '0' => 1,
        '1' => 0,
        '2' => 5,
        '3' => 7,
        '4' => 9,
        '5' => 13,
        '6' => 15,
        '7' => 17,
        '8' => 19,
        '9' => 21,
        'A' => 1,
        'B' => 0,
        'C' => 5,
        'D' => 7,
        'E' => 9,
        'F' => 13,
        'G' => 15,
        'H' => 17,
        'I' => 19,
        'J' => 21,
        'K' => 2,
        'L' => 4,
        'M' => 18,
        'N' => 20,
        'O' => 11,
        'P' => 3,
        'Q' => 6,
        'R' => 8,
        'S' => 12,
        'T' => 14,
        'U' => 16,
        'V' => 10,
        'W' => 22,
        'X' => 25,
        'Y' => 24,
        'Z' => 23,
    ];

    /**
     * Array of all available even characters.
     */
    protected $even = [
        '0' => 0,
        '1' => 1,
        '2' => 2,
        '3' => 3,
        '4' => 4,
        '5' => 5,
        '6' => 6,
        '7' => 7,
        '8' => 8,
        '9' => 9,
        'A' => 0,
        'B' => 1,
        'C' => 2,
        'D' => 3,
        'E' => 4,
        'F' => 5,
        'G' => 6,
        'H' => 7,
        'I' => 8,
        'J' => 9,
        'K' => 10,
        'L' => 11,
        'M' => 12,
        'N' => 13,
        'O' => 14,
        'P' => 15,
        'Q' => 16,
        'R' => 17,
        'S' => 18,
        'T' => 19,
        'U' => 20,
        'V' => 21,
        'W' => 22,
        'X' => 23,
        'Y' => 24,
        'Z' => 25,
    ];

    /**
     * Array of all available omocodia characters.
     */
    protected $omocodiaCodes = [
        '0' => 'L',
        '1' => 'M',
        '2' => 'N',
        '3' => 'P',
        '4' => 'Q',
        '5' => 'R',
        '6' => 'S',
        '7' => 'T',
        '8' => 'U',
        '9' => 'V',
    ];

    /**
     * Array of all available omocodia positions.
     */
    protected $omocodiaPositions = [14, 13, 12, 10, 9, 7, 6];

    private $format_regular_expressions = [
        '/^[a-z]{6}[0-9]{2}[a-z][0-9]{2}[a-z][0-9]{3}[a-z]$/i', // RSSMRA85T10A562S
        '/^[a-z]{6}[0-9]{2}[a-z][0-9]{2}[a-z][0-9]{2}[a-z]{2}$/i', // RSSMRA85T10A56NH
        '/^[a-z]{6}[0-9]{2}[a-z][0-9]{2}[a-z][0-9][a-z][0-9][a-z]$/i', // RSSMRA85T10A5S2E
        '/^[a-z]{6}[0-9]{2}[a-z][0-9]{2}[a-z][0-9][a-z]{3}$/i', // RSSMRA85T10A5SNT
        '/^[a-z]{6}[0-9]{2}[a-z][0-9]{2}[a-z]{2}[0-9]{2}[a-z]$/i', // RSSMRA85T10AR62N
        '/^[a-z]{6}[0-9]{2}[a-z][0-9]{2}[a-z]{2}[0-9][a-z]{2}$/i', // RSSMRA85T10AR6NC
        '/^[a-z]{6}[0-9]{2}[a-z][0-9]{2}[a-z]{3}[0-9][a-z]$/i', // RSSMRA85T10ARS2Z
        '/^[a-z]{6}[0-9]{2}[a-z][0-9]{2}[a-z]{5}$/i', // RSSMRA85T10ARSNO
        '/^[a-z]{6}[0-9]{2}[a-z][0-9][a-z]{2}[0-9]{3}[a-z]$/i', // RSSMRA85T1LA562V
        '/^[a-z]{6}[0-9]{2}[a-z][0-9][a-z]{2}[0-9]{2}[a-z]{2}$/i', // RSSMRA85T1LA56NK
        '/^[a-z]{6}[0-9]{2}[a-z][0-9][a-z]{2}[0-9][a-z][0-9][a-z]$/i', // RSSMRA85T1LA5S2H
        '/^[a-z]{6}[0-9]{2}[a-z][0-9][a-z]{2}[0-9][a-z]{3}$/i', // RSSMRA85T1LA5SNW
        '/^[a-z]{6}[0-9]{2}[a-z][0-9][a-z]{3}[0-9]{2}[a-z]$/i', // RSSMRA85T1LAR62Q
        '/^[a-z]{6}[0-9]{2}[a-z][0-9][a-z]{3}[0-9][a-z]{2}$/i', // RSSMRA85T1LAR6NF
        '/^[a-z]{6}[0-9]{2}[a-z][0-9][a-z]{4}[0-9][a-z]$/i', // RSSMRA85T1LARS2C
        '/^[a-z]{6}[0-9]{2}[a-z][0-9][a-z]{6}$/i', // RSSMRA85T1LARSNR
        '/^[a-z]{6}[0-9]{2}[a-z]{2}[0-9][a-z][0-9]{3}[a-z]$/i', // RSSMRA85TM0A562D
        '/^[a-z]{6}[0-9]{2}[a-z]{2}[0-9][a-z][0-9]{2}[a-z]{2}$/i', // RSSMRA85TM0A56NS
        '/^[a-z]{6}[0-9]{2}[a-z]{2}[0-9][a-z][0-9][a-z][0-9][a-z]$/i', // RSSMRA85TM0A5S2P
        '/^[a-z]{6}[0-9]{2}[a-z]{2}[0-9][a-z][0-9][a-z]{3}$/i', // RSSMRA85TM0A5SNE
        '/^[a-z]{6}[0-9]{2}[a-z]{2}[0-9][a-z]{2}[0-9]{2}[a-z]$/i', // RSSMRA85TM0AR62Y
        '/^[a-z]{6}[0-9]{2}[a-z]{2}[0-9][a-z]{2}[0-9][a-z]{2}$/i', // RSSMRA85TM0AR6NN
        '/^[a-z]{6}[0-9]{2}[a-z]{2}[0-9][a-z]{3}[0-9][a-z]$/i', // RSSMRA85TM0ARS2K
        '/^[a-z]{6}[0-9]{2}[a-z]{2}[0-9][a-z]{5}$/i', // RSSMRA85TM0ARSNZ
        '/^[a-z]{6}[0-9]{2}[a-z]{4}[0-9]{3}[a-z]$/i', // RSSMRA85TMLA562G
        '/^[a-z]{6}[0-9]{2}[a-z]{4}[0-9]{2}[a-z]{2}$/i', // RSSMRA85TMLA56NV
        '/^[a-z]{6}[0-9]{2}[a-z]{4}[0-9][a-z][0-9][a-z]$/i', // RSSMRA85TMLA5S2S
        '/^[a-z]{6}[0-9]{2}[a-z]{4}[0-9][a-z]{3}$/i', // RSSMRA85TMLA5SNH
        '/^[a-z]{6}[0-9]{2}[a-z]{5}[0-9]{2}[a-z]$/i', // RSSMRA85TMLAR62B
        '/^[a-z]{6}[0-9]{2}[a-z]{5}[0-9][a-z]{2}$/i', // RSSMRA85TMLAR6NQ
        '/^[a-z]{6}[0-9]{2}[a-z]{6}[0-9][a-z]$/i', // RSSMRA85TMLARS2N
        '/^[a-z]{6}[0-9]{2}[a-z]{8}$/i', // RSSMRA85TMLARSNC
        '/^[a-z]{6}[0-9][a-z]{2}[0-9]{2}[a-z][0-9]{3}[a-z]$/i', // RSSMRA8RT10A562E
        '/^[a-z]{6}[0-9][a-z]{2}[0-9]{2}[a-z][0-9]{2}[a-z]{2}$/i', // RSSMRA8RT10A56NT
        '/^[a-z]{6}[0-9][a-z]{2}[0-9]{2}[a-z][0-9][a-z][0-9][a-z]$/i', // RSSMRA8RT10A5S2Q
        '/^[a-z]{6}[0-9][a-z]{2}[0-9]{2}[a-z][0-9][a-z]{3}$/i', // RSSMRA8RT10A5SNF
        '/^[a-z]{6}[0-9][a-z]{2}[0-9]{2}[a-z]{2}[0-9]{2}[a-z]$/i', // RSSMRA8RT10AR62Z
        '/^[a-z]{6}[0-9][a-z]{2}[0-9]{2}[a-z]{2}[0-9][a-z]{2}$/i', // RSSMRA8RT10AR6NO
        '/^[a-z]{6}[0-9][a-z]{2}[0-9]{2}[a-z]{3}[0-9][a-z]$/i', // RSSMRA8RT10ARS2L
        '/^[a-z]{6}[0-9][a-z]{2}[0-9]{2}[a-z]{5}$/i', // RSSMRA8RT10ARSNA
        '/^[a-z]{6}[0-9][a-z]{2}[0-9][a-z]{2}[0-9]{3}[a-z]$/i', // RSSMRA8RT1LA562H
        '/^[a-z]{6}[0-9][a-z]{2}[0-9][a-z]{2}[0-9]{2}[a-z]{2}$/i', // RSSMRA8RT1LA56NW
        '/^[a-z]{6}[0-9][a-z]{2}[0-9][a-z]{2}[0-9][a-z][0-9][a-z]$/i', // RSSMRA8RT1LA5S2T
        '/^[a-z]{6}[0-9][a-z]{2}[0-9][a-z]{2}[0-9][a-z]{3}$/i', // RSSMRA8RT1LA5SNI
        '/^[a-z]{6}[0-9][a-z]{2}[0-9][a-z]{3}[0-9]{2}[a-z]$/i', // RSSMRA8RT1LAR62C
        '/^[a-z]{6}[0-9][a-z]{2}[0-9][a-z]{3}[0-9][a-z]{2}$/i', // RSSMRA8RT1LAR6NR
        '/^[a-z]{6}[0-9][a-z]{2}[0-9][a-z]{4}[0-9][a-z]$/i', // RSSMRA8RT1LARS2O
        '/^[a-z]{6}[0-9][a-z]{2}[0-9][a-z]{6}$/i', // RSSMRA8RT1LARSND
        '/^[a-z]{6}[0-9][a-z]{3}[0-9][a-z][0-9]{3}[a-z]$/i', // RSSMRA8RTM0A562P
        '/^[a-z]{6}[0-9][a-z]{3}[0-9][a-z][0-9]{2}[a-z]{2}$/i', // RSSMRA8RTM0A56NE
        '/^[a-z]{6}[0-9][a-z]{3}[0-9][a-z][0-9][a-z][0-9][a-z]$/i', // RSSMRA8RTM0A5S2B
        '/^[a-z]{6}[0-9][a-z]{3}[0-9][a-z][0-9][a-z]{3}$/i', // RSSMRA8RTM0A5SNQ
        '/^[a-z]{6}[0-9][a-z]{3}[0-9][a-z]{2}[0-9]{2}[a-z]$/i', // RSSMRA8RTM0AR62K
        '/^[a-z]{6}[0-9][a-z]{3}[0-9][a-z]{2}[0-9][a-z]{2}$/i', // RSSMRA8RTM0AR6NZ
        '/^[a-z]{6}[0-9][a-z]{3}[0-9][a-z]{3}[0-9][a-z]$/i', // RSSMRA8RTM0ARS2W
        '/^[a-z]{6}[0-9][a-z]{3}[0-9][a-z]{5}$/i', // RSSMRA8RTM0ARSNL
        '/^[a-z]{6}[0-9][a-z]{5}[0-9]{3}[a-z]$/i', // RSSMRA8RTMLA562S
        '/^[a-z]{6}[0-9][a-z]{5}[0-9]{2}[a-z]{2}$/i', // RSSMRA8RTMLA56NH
        '/^[a-z]{6}[0-9][a-z]{5}[0-9][a-z][0-9][a-z]$/i', // RSSMRA8RTMLA5S2E
        '/^[a-z]{6}[0-9][a-z]{5}[0-9][a-z]{3}$/i', // RSSMRA8RTMLA5SNT
        '/^[a-z]{6}[0-9][a-z]{6}[0-9]{2}[a-z]$/i', // RSSMRA8RTMLAR62N
        '/^[a-z]{6}[0-9][a-z]{6}[0-9][a-z]{2}$/i', // RSSMRA8RTMLAR6NC
        '/^[a-z]{6}[0-9][a-z]{7}[0-9][a-z]$/i', // RSSMRA8RTMLARS2Z
        '/^[a-z]{6}[0-9][a-z]{9}$/i', // RSSMRA8RTMLARSNO
        '/^[a-z]{7}[0-9][a-z][0-9]{2}[a-z][0-9]{3}[a-z]$/i', // RSSMRAU5T10A562P
        '/^[a-z]{7}[0-9][a-z][0-9]{2}[a-z][0-9]{2}[a-z]{2}$/i', // RSSMRAU5T10A56NE
        '/^[a-z]{7}[0-9][a-z][0-9]{2}[a-z][0-9][a-z][0-9][a-z]$/i', // RSSMRAU5T10A5S2B
        '/^[a-z]{7}[0-9][a-z][0-9]{2}[a-z][0-9][a-z]{3}$/i', // RSSMRAU5T10A5SNQ
        '/^[a-z]{7}[0-9][a-z][0-9]{2}[a-z]{2}[0-9]{2}[a-z]$/i', // RSSMRAU5T10AR62K
        '/^[a-z]{7}[0-9][a-z][0-9]{2}[a-z]{2}[0-9][a-z]{2}$/i', // RSSMRAU5T10AR6NZ
        '/^[a-z]{7}[0-9][a-z][0-9]{2}[a-z]{3}[0-9][a-z]$/i', // RSSMRAU5T10ARS2W
        '/^[a-z]{7}[0-9][a-z][0-9]{2}[a-z]{5}$/i', // RSSMRAU5T10ARSNL
        '/^[a-z]{7}[0-9][a-z][0-9][a-z]{2}[0-9]{3}[a-z]$/i', // RSSMRAU5T1LA562S
        '/^[a-z]{7}[0-9][a-z][0-9][a-z]{2}[0-9]{2}[a-z]{2}$/i', // RSSMRAU5T1LA56NH
        '/^[a-z]{7}[0-9][a-z][0-9][a-z]{2}[0-9][a-z][0-9][a-z]$/i', // RSSMRAU5T1LA5S2E
        '/^[a-z]{7}[0-9][a-z][0-9][a-z]{2}[0-9][a-z]{3}$/i', // RSSMRAU5T1LA5SNT
        '/^[a-z]{7}[0-9][a-z][0-9][a-z]{3}[0-9]{2}[a-z]$/i', // RSSMRAU5T1LAR62N
        '/^[a-z]{7}[0-9][a-z][0-9][a-z]{3}[0-9][a-z]{2}$/i', // RSSMRAU5T1LAR6NC
        '/^[a-z]{7}[0-9][a-z][0-9][a-z]{4}[0-9][a-z]$/i', // RSSMRAU5T1LARS2Z
        '/^[a-z]{7}[0-9][a-z][0-9][a-z]{6}$/i', // RSSMRAU5T1LARSNO
        '/^[a-z]{7}[0-9][a-z]{2}[0-9][a-z][0-9]{3}[a-z]$/i', // RSSMRAU5TM0A562A
        '/^[a-z]{7}[0-9][a-z]{2}[0-9][a-z][0-9]{2}[a-z]{2}$/i', // RSSMRAU5TM0A56NP
        '/^[a-z]{7}[0-9][a-z]{2}[0-9][a-z][0-9][a-z][0-9][a-z]$/i', // RSSMRAU5TM0A5S2M
        '/^[a-z]{7}[0-9][a-z]{2}[0-9][a-z][0-9][a-z]{3}$/i', // RSSMRAU5TM0A5SNB
        '/^[a-z]{7}[0-9][a-z]{2}[0-9][a-z]{2}[0-9]{2}[a-z]$/i', // RSSMRAU5TM0AR62V
        '/^[a-z]{7}[0-9][a-z]{2}[0-9][a-z]{2}[0-9][a-z]{2}$/i', // RSSMRAU5TM0AR6NK
        '/^[a-z]{7}[0-9][a-z]{2}[0-9][a-z]{3}[0-9][a-z]$/i', // RSSMRAU5TM0ARS2H
        '/^[a-z]{7}[0-9][a-z]{2}[0-9][a-z]{5}$/i', // RSSMRAU5TM0ARSNW
        '/^[a-z]{7}[0-9][a-z]{4}[0-9]{3}[a-z]$/i', // RSSMRAU5TMLA562D
        '/^[a-z]{7}[0-9][a-z]{4}[0-9]{2}[a-z]{2}$/i', // RSSMRAU5TMLA56NS
        '/^[a-z]{7}[0-9][a-z]{4}[0-9][a-z][0-9][a-z]$/i', // RSSMRAU5TMLA5S2P
        '/^[a-z]{7}[0-9][a-z]{4}[0-9][a-z]{3}$/i', // RSSMRAU5TMLA5SNE
        '/^[a-z]{7}[0-9][a-z]{5}[0-9]{2}[a-z]$/i', // RSSMRAU5TMLAR62Y
        '/^[a-z]{7}[0-9][a-z]{5}[0-9][a-z]{2}$/i', // RSSMRAU5TMLAR6NN
        '/^[a-z]{7}[0-9][a-z]{6}[0-9][a-z]$/i', // RSSMRAU5TMLARS2K
        '/^[a-z]{7}[0-9][a-z]{8}$/i', // RSSMRAU5TMLARSNZ
        '/^[a-z]{9}[0-9]{2}[a-z][0-9]{3}[a-z]$/i', // RSSMRAURT10A562B
        '/^[a-z]{9}[0-9]{2}[a-z][0-9]{2}[a-z]{2}$/i', // RSSMRAURT10A56NQ
        '/^[a-z]{9}[0-9]{2}[a-z][0-9][a-z][0-9][a-z]$/i', // RSSMRAURT10A5S2N
        '/^[a-z]{9}[0-9]{2}[a-z][0-9][a-z]{3}$/i', // RSSMRAURT10A5SNC
        '/^[a-z]{9}[0-9]{2}[a-z]{2}[0-9]{2}[a-z]$/i', // RSSMRAURT10AR62W
        '/^[a-z]{9}[0-9]{2}[a-z]{2}[0-9][a-z]{2}$/i', // RSSMRAURT10AR6NL
        '/^[a-z]{9}[0-9]{2}[a-z]{3}[0-9][a-z]$/i', // RSSMRAURT10ARS2I
        '/^[a-z]{9}[0-9]{2}[a-z]{5}$/i', // RSSMRAURT10ARSNX
        '/^[a-z]{9}[0-9][a-z]{2}[0-9]{3}[a-z]$/i', // RSSMRAURT1LA562E
        '/^[a-z]{9}[0-9][a-z]{2}[0-9]{2}[a-z]{2}$/i', // RSSMRAURT1LA56NT
        '/^[a-z]{9}[0-9][a-z]{2}[0-9][a-z][0-9][a-z]$/i', // RSSMRAURT1LA5S2Q
        '/^[a-z]{9}[0-9][a-z]{2}[0-9][a-z]{3}$/i', // RSSMRAURT1LA5SNF
        '/^[a-z]{9}[0-9][a-z]{3}[0-9]{2}[a-z]$/i', // RSSMRAURT1LAR62Z
        '/^[a-z]{9}[0-9][a-z]{3}[0-9][a-z]{2}$/i', // RSSMRAURT1LAR6NO
        '/^[a-z]{9}[0-9][a-z]{4}[0-9][a-z]$/i', // RSSMRAURT1LARS2L
        '/^[a-z]{9}[0-9][a-z]{6}$/i', // RSSMRAURT1LARSNA
        '/^[a-z]{10}[0-9][a-z][0-9]{3}[a-z]$/i', // RSSMRAURTM0A562M
        '/^[a-z]{10}[0-9][a-z][0-9]{2}[a-z]{2}$/i', // RSSMRAURTM0A56NB
        '/^[a-z]{10}[0-9][a-z][0-9][a-z][0-9][a-z]$/i', // RSSMRAURTM0A5S2Y
        '/^[a-z]{10}[0-9][a-z][0-9][a-z]{3}$/i', // RSSMRAURTM0A5SNN
        '/^[a-z]{10}[0-9][a-z]{2}[0-9]{2}[a-z]$/i', // RSSMRAURTM0AR62H
        '/^[a-z]{10}[0-9][a-z]{2}[0-9][a-z]{2}$/i', // RSSMRAURTM0AR6NW
        '/^[a-z]{10}[0-9][a-z]{3}[0-9][a-z]$/i', // RSSMRAURTM0ARS2T
        '/^[a-z]{10}[0-9][a-z]{5}$/i', // RSSMRAURTM0ARSNI
        '/^[a-z]{12}[0-9]{3}[a-z]$/i', // RSSMRAURTMLA562P
        '/^[a-z]{12}[0-9]{2}[a-z]{2}$/i', // RSSMRAURTMLA56NE
        '/^[a-z]{12}[0-9][a-z][0-9][a-z]$/i', // RSSMRAURTMLA5S2B
        '/^[a-z]{12}[0-9][a-z]{3}$/i', // RSSMRAURTMLA5SNQ
        '/^[a-z]{13}[0-9]{2}[a-z]$/i', // RSSMRAURTMLAR62K
        '/^[a-z]{13}[0-9][a-z]{2}$/i', // RSSMRAURTMLAR6NZ
        '/^[a-z]{14}[0-9][a-z]$/i', // RSSMRAURTMLARS2W
        '/^[a-z]{16}$/i', // RSSMRAURTMLARSNL
    ];

    public function __construct(protected bool $check_driver = false, protected bool $check_competitor = false)
    {
        //
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->check_driver && (($this->input('driver_nationality') ?? false || $this->input('driver_licence_type') ?? false))) {

            if (blank($value) &&
                (Str::contains(Str::lower($this->input('driver_nationality', '')), ['italian', 'italiana', 'italia', 'italy'])
                    || (filled($this->input('driver_licence_type')) && $this->input('driver_licence_type') !== DriverLicence::FOREIGN->value))) {

                $fail('validation.required')->translate();

                return;
            }

        }

        if ($this->check_competitor && (($this->input('competitor_nationality') ?? false || $this->input('competitor_licence_type') ?? false))) {

            if (blank($value) &&
                (Str::contains(Str::lower($this->input('competitor_nationality', '')), ['italian', 'italiana', 'italia', 'italy'])
                    || (filled($this->input('competitor_licence_type')) && $this->input('competitor_licence_type') !== CompetitorLicence::FOREIGN->value))) {

                $fail('validation.required')->translate();

                return;
            }

        }

        if (! is_string($value)) {
            $fail('validation.fiscal_code')->translate();

            return;
        }

        // normalize to uppercase
        $value = Str::upper($value);

        try {
            $this->validateLength($value);

            $this->validateFormat($value);

            $this->validateCheckDigit($value);

        } catch (InvalidArgumentException $e) {
            $fail('validation.fiscal_code')->translate();
        }
    }

    /**
     * Set the data under validation.
     *
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Calculate the sum by the given dictionary for the given temporary codice fiscale.
     *
     * @param  string  $temporaryCodiceFiscale  The temporary codice fiscale.
     * @param  array  $dictionaryArray  The dictionary array
     * @param  int  $i  The start index value.
     * @return int
     */
    protected function calculateSumByDictionary($temporaryCodiceFiscale, $dictionaryArray, $i)
    {
        $sum = 0;
        for (; $i < 15; $i += 2) {
            $k = $temporaryCodiceFiscale[$i] ?? '';
            $sum += $dictionaryArray[$k] ?? 0;
        }

        return $sum;
    }

    /**
     * Calculate the check digit.
     *
     * @param  string  $temporaryCodiceFiscale  The first part of the codice fiscale.
     * @return string The check digit part of the codice fiscale.
     */
    protected function calculateCheckDigit($temporaryCodiceFiscale)
    {
        $sumEven = $this->calculateSumByDictionary($temporaryCodiceFiscale, $this->even, 1);
        $sumOdd = $this->calculateSumByDictionary($temporaryCodiceFiscale, $this->odd, 0);

        return chr(($sumOdd + $sumEven) % 26 + 65);
    }

    protected function input(string $value, mixed $default = null): mixed
    {
        return $this->data[$value] ?? $default;
    }

    /**
     * Validates length
     *
     * @throws InvalidArgumentException
     */
    private function validateLength($value)
    {
        if (blank($value)) {
            throw new InvalidArgumentException('The codice fiscale to validate is empty');
        }

        $value = mb_trim($value);

        if (mb_strlen($value) !== 16) {
            throw new InvalidArgumentException('The codice fiscale to validate has an invalid length');
        }
    }

    /**
     * Validates format
     *
     * @throws InvalidArgumentException
     */
    private function validateFormat($value)
    {
        $regexpValid = false;

        foreach ($this->format_regular_expressions as $regex) {
            if (preg_match($regex, $value)) {
                $regexpValid = true;
                break;
            }
        }

        if (! $regexpValid) {
            throw new InvalidArgumentException('The codice fiscale to validate has an invalid format');
        }
    }

    /**
     * Validates check digit
     *
     * @throws InvalidArgumentException
     */
    private function validateCheckDigit($value)
    {
        $checkDigit = $this->calculateCheckDigit($value);
        if ($checkDigit !== $value[15]) {
            throw new InvalidArgumentException('The codice fiscale to validate has an invalid control character');
        }
    }
}
