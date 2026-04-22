<?php

declare(strict_types=1);

namespace Tests\Feature\Blade;

use App\Models\Championship;
use App\Models\Race;
use App\Models\RegistrationForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UseCompleteRegistrationFormDirectiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_falls_back_to_config_when_race_has_no_registration_form(): void
    {
        config(['races.registration.form' => 'complete']);

        $race = Race::factory()->for(Championship::factory())->create(['registration_form' => null]);

        $view = $this->blade('@useCompleteRegistrationForm($race) yes @else no @enduseCompleteRegistrationForm', ['race' => $race]);

        $view->assertSee('yes');
        $view->assertDontSee('no');
    }

    public function test_falls_back_to_config_minimal_when_race_has_no_registration_form(): void
    {
        config(['races.registration.form' => 'minimal']);

        $race = Race::factory()->for(Championship::factory())->create(['registration_form' => null]);

        $view = $this->blade('@useCompleteRegistrationForm($race) yes @else no @enduseCompleteRegistrationForm', ['race' => $race]);

        $view->assertSee('no');
        $view->assertDontSee('yes');
    }

    public function test_race_registration_form_takes_precedence_over_config(): void
    {
        config(['races.registration.form' => 'minimal']);

        $race = Race::factory()->for(Championship::factory())->create(['registration_form' => RegistrationForm::Complete]);

        $view = $this->blade('@useCompleteRegistrationForm($race) yes @else no @enduseCompleteRegistrationForm', ['race' => $race]);

        $view->assertSee('yes');
    }

    public function test_race_minimal_form_overrides_config_complete(): void
    {
        config(['races.registration.form' => 'complete']);

        $race = Race::factory()->for(Championship::factory())->create(['registration_form' => RegistrationForm::Minimal]);

        $view = $this->blade('@useCompleteRegistrationForm($race) yes @else no @enduseCompleteRegistrationForm', ['race' => $race]);

        $view->assertSee('no');
    }

    public function test_championship_registration_form_used_when_race_has_none(): void
    {
        config(['races.registration.form' => 'minimal']);

        $championship = Championship::factory()->create(['registration_form' => RegistrationForm::Complete]);
        $race = Race::factory()->for($championship)->create(['registration_form' => null]);

        $race->load('championship');

        $view = $this->blade('@useCompleteRegistrationForm($race) yes @else no @enduseCompleteRegistrationForm', ['race' => $race]);

        $view->assertSee('yes');
    }

    public function test_race_registration_form_takes_precedence_over_championship(): void
    {
        config(['races.registration.form' => 'minimal']);

        $championship = Championship::factory()->create(['registration_form' => RegistrationForm::Complete]);
        $race = Race::factory()->for($championship)->create(['registration_form' => RegistrationForm::Minimal]);

        $race->load('championship');

        $view = $this->blade('@useCompleteRegistrationForm($race) yes @else no @enduseCompleteRegistrationForm', ['race' => $race]);

        $view->assertSee('no');
    }

    public function test_directive_works_without_race_argument(): void
    {
        config(['races.registration.form' => 'complete']);

        $view = $this->blade('@useCompleteRegistrationForm() yes @else no @enduseCompleteRegistrationForm');

        $view->assertSee('yes');
    }
}
