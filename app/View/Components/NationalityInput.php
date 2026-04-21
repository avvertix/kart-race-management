<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\View\Component;
use PrinsFrank\Standards\Country\CountryAlpha2;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use PrinsFrank\Standards\Region\GeographicRegion;

class NationalityInput extends Component
{
    private const PRIORITY_COUNTRY_CODES = [
        CountryAlpha2::Italy,
        CountryAlpha2::Austria,
        CountryAlpha2::Switzerland,
        CountryAlpha2::Germany,
        CountryAlpha2::France,
        CountryAlpha2::Spain,
        CountryAlpha2::Poland,
        CountryAlpha2::Malta,
    ];

    public string $listId;

    /** @var array<int, string> */
    public array $priorityCountries;

    /** @var array<int, string> */
    public array $otherCountries;

    public function __construct(
        public string $id,
        public string $name,
        public ?string $value = null,
    ) {
        $this->listId = $id.'_options';

        $language = LanguageAlpha2::tryFrom(app()->getLocale()) ?? LanguageAlpha2::English;

        $priorityNumericCodes = array_map(
            fn (CountryAlpha2 $c) => $c->toCountryNumeric()->value,
            self::PRIORITY_COUNTRY_CODES
        );

        $allCountries = GeographicRegion::Europe->getAllSubCountries();

        $this->priorityCountries = array_map(
            fn (CountryAlpha2 $c) => $c->getNameInLanguage($language),
            self::PRIORITY_COUNTRY_CODES
        );

        $otherNames = [];
        foreach ($allCountries as $country) {
            if (in_array($country->value, $priorityNumericCodes, true)) {
                continue;
            }
            $otherNames[] = $country->getNameInLanguage($language);
        }

        sort($otherNames);

        $this->otherCountries = $otherNames;
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('components.nationality-input');
    }
}
