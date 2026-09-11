<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\User;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class UpdateChampionshipBibSettingsControllerTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_allow_different_bibs_can_be_enabled()
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()->create(['registration_settings' => ['allow_different_bibs' => false]]);

        $response = $this
            ->actingAs($user)
            ->put(route('championships.bib-settings.update', $championship), [
                'allow_different_bibs' => 'true',
            ]);

        $response->assertRedirect(route('championships.show', $championship));
        $response->assertSessionHas('flash.banner', __(':championship BIB settings updated.', [
            'championship' => $championship->title,
        ]));

        $this->assertTrue($championship->refresh()->registration_settings->allow_different_bibs);
    }

    public function test_allow_different_bibs_can_be_disabled()
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()->create(['registration_settings' => ['allow_different_bibs' => true]]);

        $response = $this
            ->actingAs($user)
            ->put(route('championships.bib-settings.update', $championship), [
                'allow_different_bibs' => 'false',
            ]);

        $response->assertRedirect(route('championships.show', $championship));

        $this->assertFalse($championship->refresh()->registration_settings->allow_different_bibs);
    }

    public function test_shared_bib_licences_can_be_configured()
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->put(route('championships.bib-settings.update', $championship), [
                'shared_bib' => '15',
                'shared_bib_licences' => "497339\n498963",
            ]);

        $response->assertRedirect(route('championships.show', $championship));

        $settings = $championship->refresh()->registration_settings;

        $this->assertEquals(15, $settings->shared_bib);
        $this->assertEquals(['497339', '498963'], $settings->shared_bib_licences);
    }

    public function test_shared_bib_licences_accepts_comma_separated_values()
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->put(route('championships.bib-settings.update', $championship), [
                'shared_bib' => '15',
                'shared_bib_licences' => '497339, 498963',
            ]);

        $response->assertRedirect(route('championships.show', $championship));

        $this->assertEquals(['497339', '498963'], $championship->refresh()->registration_settings->shared_bib_licences);
    }

    public function test_shared_bib_licences_can_be_cleared()
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()->create(['registration_settings' => [
            'shared_bib_licences' => ['497339', '498963'],
            'shared_bib' => 15,
        ]]);

        $response = $this
            ->actingAs($user)
            ->put(route('championships.bib-settings.update', $championship), []);

        $response->assertRedirect(route('championships.show', $championship));

        $settings = $championship->refresh()->registration_settings;

        $this->assertEquals([], $settings->shared_bib_licences);
        $this->assertNull($settings->shared_bib);
    }

    public function test_shared_bib_is_required_when_shared_bib_licences_are_given()
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->put(route('championships.bib-settings.update', $championship), [
                'shared_bib_licences' => '497339',
            ]);

        $response->assertSessionHasErrors('shared_bib');
    }
}
