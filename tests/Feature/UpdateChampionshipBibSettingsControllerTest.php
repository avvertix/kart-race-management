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
}
