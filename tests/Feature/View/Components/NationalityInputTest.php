<?php

declare(strict_types=1);

namespace Tests\Feature\View\Components;

use App\View\Components\NationalityInput;
use Tests\TestCase;

class NationalityInputTest extends TestCase
{
    public function test_priority_countries_are_listed_first(): void
    {
        $component = new NationalityInput(id: 'driver_nationality', name: 'driver_nationality');

        $this->assertSame(['Italy', 'Austria', 'Switzerland', 'Germany', 'France', 'Spain', 'Poland', 'Malta'], $component->priorityCountries);
    }

    public function test_priority_countries_are_not_in_other_countries(): void
    {
        $component = new NationalityInput(id: 'driver_nationality', name: 'driver_nationality');

        foreach ($component->priorityCountries as $country) {
            $this->assertNotContains($country, $component->otherCountries, "'{$country}' should not appear in otherCountries");
        }
    }

    public function test_other_countries_are_sorted_alphabetically(): void
    {
        $component = new NationalityInput(id: 'driver_nationality', name: 'driver_nationality');

        $sorted = $component->otherCountries;
        sort($sorted);

        $this->assertSame($sorted, $component->otherCountries);
    }

    public function test_list_id_is_derived_from_id(): void
    {
        $component = new NationalityInput(id: 'driver_nationality', name: 'driver_nationality');

        $this->assertSame('driver_nationality_options', $component->listId);
    }

    public function test_renders_input_with_datalist(): void
    {
        $view = $this->blade(
            '<x-nationality-input id="driver_nationality" name="driver_nationality" />',
        );

        $view->assertSee('list="driver_nationality_options"', false);
        $view->assertSee('<datalist id="driver_nationality_options">', false);
        $view->assertSee('Italy');
        $view->assertSee('Germany');
    }

    public function test_renders_with_selected_value(): void
    {
        $view = $this->blade(
            '<x-nationality-input id="driver_nationality" name="driver_nationality" value="Italy" />',
        );

        $view->assertSee('value="Italy"', false);
    }
}
