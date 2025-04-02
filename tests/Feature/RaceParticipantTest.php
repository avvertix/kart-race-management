<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Championship;
use App\Models\DriverLicence;
use App\Models\Participant;
use App\Models\Race;
use App\Models\Sex;
use App\Models\TrashedParticipant;
use App\Models\User;
use App\Notifications\ConfirmParticipantRegistration;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Tests\CreateCompetitor;
use Tests\CreateDriver;
use Tests\CreateMechanic;
use Tests\CreateVehicle;
use Tests\TestCase;

class RaceParticipantTest extends TestCase
{
    use CreateCompetitor;
    use CreateDriver;
    use CreateMechanic;
    use CreateVehicle;
    use FastRefreshDatabase;

    public function test_registration_form_loads()
    {
        $user = User::factory()->racemanager()->create();

        $race = Race::factory()->create();

        $category = Category::factory()->recycle($race->championship)->create();

        $response = $this
            ->actingAs($user)
            ->get(route('races.participants.create', $race));

        $response->assertOk();

        $response->assertViewHas('race', $race);

        $response->assertViewHas('categories');

    }

    public function test_template_participant_included_when_race_within_same_championship()
    {
        $user = User::factory()->racemanager()->create();

        $race = Race::factory()->create();

        $category = Category::factory()->recycle($race->championship)->create();

        $existingParticipant = Participant::factory()->create([
            'bib' => 100,
            'championship_id' => $race->championship_id,
            'race_id' => $race->getKey(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('races.participants.create', $race).'?from='.$existingParticipant->uuid);

        $response->assertOk();

        $response->assertViewHas('race', $race);

        $response->assertViewHas('categories');

        $response->assertViewHas('participant', $existingParticipant);

    }

    public function test_template_participant_ignored_if_from_other_championship()
    {
        $user = User::factory()->racemanager()->create();

        $race = Race::factory()->create();

        $otherRace = Race::factory()->create();

        $category = Category::factory()->recycle($race->championship)->create();

        $existingParticipant = Participant::factory()->create([
            'bib' => 100,
            'championship_id' => $otherRace->championship_id,
            'race_id' => $otherRace->getKey(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('races.participants.create', ['race' => $race, 'from' => $existingParticipant->uuid]));

        $response->assertOk();

        $response->assertViewHas('race', $race);

        $response->assertViewHas('categories');

        $response->assertViewHas('participant', null);

    }

    public function test_participant_can_be_registered()
    {
        Notification::fake();

        $user = User::factory()->racemanager()->create();

        $race = Race::factory()->create();

        $category = Category::factory()->recycle($race->championship)->create();

        $response = $this->actingAs($user)
            ->from(route('races.participants.create', $race))
            ->post(route('races.participants.store', $race), [
                'bib' => 100,
                'category' => $category->ulid,
                ...$this->generateValidDriver(),
                ...$this->generateValidCompetitor(),
                ...$this->generateValidMechanic(),
                ...$this->generateValidVehicle(),
                'consent_privacy' => true,
                'bonus' => 'true',
            ]);

        $response->assertRedirectToRoute('races.participants.index', $race);

        $response->assertSessionHasNoErrors();

        $response->assertSessionHas('flash.banner', '100 John Racer added.');

        $participant = Participant::first();

        $this->assertInstanceOf(Participant::class, $participant);

        $this->assertEquals(100, $participant->bib);
        $this->assertEquals($category->ulid, $participant->category);
        $this->assertTrue($participant->racingCategory->is($category));
        $this->assertEquals('John', $participant->first_name);
        $this->assertEquals('Racer', $participant->last_name);

        $this->assertEquals([
            'first_name' => 'John',
            'last_name' => 'Racer',
            'licence_type' => 10,
            'licence_number' => 'D0001',
            'licence_renewed_at' => null,
            'nationality' => 'Italy',
            'email' => 'john@racer.local',
            'phone' => '555555555',
            'birth_date' => '1999-11-11',
            'birth_place' => 'Milan',
            'medical_certificate_expiration_date' => today()->addYear()->toDateString(),
            'residence_address' => [
                'address' => 'via dei Platani, 40',
                'city' => 'Milan',
                'province' => 'Milan',
                'postal_code' => '20146',
            ],
            'sex' => Sex::MALE->value,
            'fiscal_code' => 'DRV-FC',
        ], $participant->driver);

        $this->assertEquals([
            'first_name' => 'Parent',
            'last_name' => 'Racer',
            'licence_type' => 10,
            'licence_number' => 'C0002',
            'licence_renewed_at' => null,
            'nationality' => 'Italy',
            'email' => 'parent@racer.local',
            'phone' => '54444444',
            'birth_date' => '1979-11-11',
            'birth_place' => 'Milan',
            'residence_address' => [
                'address' => 'via dei Platani, 40',
                'city' => 'Milan',
                'province' => 'Milan',
                'postal_code' => '20146',
            ],
            'fiscal_code' => 'CMPT-FC',
        ], $participant->competitor);

        $this->assertEquals('Mechanic Racer', $participant->mechanic['name']);
        $this->assertEquals('M0003', $participant->mechanic['licence_number']);

        $this->assertCount(1, $participant->vehicles);

        $this->assertEquals('Chassis', $participant->vehicles[0]['chassis_manufacturer']);
        $this->assertEquals('engine manufacturer', $participant->vehicles[0]['engine_manufacturer']);
        $this->assertEquals('engine model', $participant->vehicles[0]['engine_model']);
        $this->assertEquals('Oil Manufacturer', $participant->vehicles[0]['oil_manufacturer']);
        $this->assertEquals('Oil Type', $participant->vehicles[0]['oil_type']);
        $this->assertEquals('4', $participant->vehicles[0]['oil_percentage']);

        Notification::assertCount(2);

        Notification::assertSentTo($participant, function (ConfirmParticipantRegistration $notification, $channels) {
            return $notification->target === 'driver';
        });

        Notification::assertSentTo($participant, function (ConfirmParticipantRegistration $notification, $channels) {
            return $notification->target === 'competitor';
        });

    }

    public function test_participant_without_competitor_can_be_registered()
    {
        $user = User::factory()->racemanager()->create();

        $race = Race::factory()->create();

        $category = Category::factory()->recycle($race->championship)->create();

        $response = $this->actingAs($user)
            ->from(route('races.participants.create', $race))
            ->post(route('races.participants.store', $race), [
                'bib' => 100,
                'category' => $category->ulid,

                'driver_licence_type' => DriverLicence::LOCAL_NATIONAL->value,

                ...$this->generateValidDriver(),
                ...$this->generateValidMechanic(),
                ...$this->generateValidVehicle(),

                'competitor_first_name' => null,
                'competitor_last_name' => null,
                'competitor_licence_number' => null,
                'competitor_nationality' => null,
                'competitor_email' => null,
                'competitor_phone' => null,
                'competitor_birth_date' => null,
                'competitor_birth_place' => null,
                'competitor_residence_address' => null,
                'competitor_residence_postal_code' => null,
                'competitor_residence_city' => null,
                'competitor_residence_province' => null,

                'consent_privacy' => true,

            ]);

        $response->assertRedirectToRoute('races.participants.index', $race);

        $response->assertSessionHasNoErrors();

        $response->assertSessionHas('flash.banner', '100 John Racer added.');

        $participant = Participant::first();

        $this->assertInstanceOf(Participant::class, $participant);

        $this->assertEquals(100, $participant->bib);
        $this->assertEquals($category->ulid, $participant->category);
        $this->assertTrue($participant->racingCategory->is($category));
        $this->assertEquals('John', $participant->first_name);
        $this->assertEquals('Racer', $participant->last_name);

        $this->assertEquals([
            'first_name' => 'John',
            'last_name' => 'Racer',
            'licence_type' => 10,
            'licence_number' => 'D0001',
            'licence_renewed_at' => null,
            'nationality' => 'Italy',
            'email' => 'john@racer.local',
            'phone' => '555555555',
            'birth_date' => '1999-11-11',
            'birth_place' => 'Milan',
            'medical_certificate_expiration_date' => today()->addYear()->toDateString(),
            'residence_address' => [
                'address' => 'via dei Platani, 40',
                'city' => 'Milan',
                'province' => 'Milan',
                'postal_code' => '20146',
            ],
            'sex' => Sex::MALE->value,
            'fiscal_code' => 'DRV-FC',
        ], $participant->driver);

        $this->assertNull($participant->competitor);

        $this->assertEquals('Mechanic Racer', $participant->mechanic['name']);
        $this->assertEquals('M0003', $participant->mechanic['licence_number']);

        $this->assertCount(1, $participant->vehicles);

        $this->assertFalse($participant->use_bonus);

        $this->assertEquals('Chassis', $participant->vehicles[0]['chassis_manufacturer']);
        $this->assertEquals('engine manufacturer', $participant->vehicles[0]['engine_manufacturer']);
        $this->assertEquals('engine model', $participant->vehicles[0]['engine_model']);
        $this->assertEquals('Oil Manufacturer', $participant->vehicles[0]['oil_manufacturer']);
        $this->assertEquals('Oil Type', $participant->vehicles[0]['oil_type']);
        $this->assertEquals('4', $participant->vehicles[0]['oil_percentage']);

    }

    public function test_participant_cannot_register_using_existing_bib_in_same_race()
    {
        $user = User::factory()->racemanager()->create();

        $race = Race::factory()->create();

        $existingParticipant = Participant::factory()
            ->recycle($race->championship)
            ->category()
            ->create([
                'bib' => 100,
                'race_id' => $race->getKey(),
            ]);

        $response = $this->actingAs($user)
            ->from(route('races.participants.create', $race))
            ->post(route('races.participants.store', $race), [
                'bib' => 100,
                'category' => $race->championship->categories()->first()->ulid,
                ...$this->generateValidDriver(),
                ...$this->generateValidCompetitor(),
                ...$this->generateValidMechanic(),
                ...$this->generateValidVehicle(),
                'consent_privacy' => true,
                'bonus' => 'false',
            ]);

        $response->assertRedirectToRoute('races.participants.create', $race);

        $response->assertSessionHasErrors('bib');

        $this->assertEquals(1, Participant::where('bib', 100)->count());

    }

    public function test_participant_cannot_register_using_same_licence_twice_in_race()
    {
        $user = User::factory()->racemanager()->create();

        $race = Race::factory()->create();

        $existingParticipant = Participant::factory()
            ->recycle($race->championship)
            ->category()
            ->create([
                'bib' => 100,
                'race_id' => $race->getKey(),
                'driver_licence' => hash('sha512', 'D0001'),
                'driver' => [
                    'licence_number' => 'D0001',
                ],
            ]);

        $response = $this->actingAs($user)
            ->from(route('races.participants.create', $race))
            ->post(route('races.participants.store', $race), [
                'bib' => 200,
                'category' => $race->championship->categories()->first()->ulid,
                ...$this->generateValidDriver(),
                ...$this->generateValidVehicle(),
                'consent_privacy' => true,
                'bonus' => 'false',
            ]);

        $response->assertRedirectToRoute('races.participants.create', $race);

        $response->assertSessionHasErrors('driver_licence_number');

        $this->assertEquals(1, Participant::where('bib', 100)->count());

    }

    public function test_participant_cannot_register_using_existing_bib_in_championship()
    {
        $user = User::factory()->racemanager()->create();

        $championship = Championship::factory()
            ->has(Race::factory()->count(2))
            ->create();

        $category = Category::factory()->recycle($championship)->create();

        [$race, $otherRace] = $championship->races;

        $existingParticipant = Participant::factory()->create([
            'bib' => 100,
            'championship_id' => $championship->getKey(),
            'race_id' => $otherRace->getKey(),
        ]);

        $response = $this->actingAs($user)
            ->from(route('races.participants.create', $race))
            ->post(route('races.participants.store', $race), [
                'bib' => 100,
                'category' => $category->ulid,
                ...$this->generateValidDriver(),
                ...$this->generateValidCompetitor(),
                ...$this->generateValidMechanic(),
                ...$this->generateValidVehicle(),
                'consent_privacy' => true,
                'bonus' => 'false',
            ]);

        $response->assertRedirectToRoute('races.participants.create', $race);

        $response->assertSessionHasErrors('bib');

        $this->assertEquals(1, Participant::where('bib', 100)->count());

    }

    public function test_participant_cannot_register_while_another_is_submitting_with_same_bib()
    {
        $user = User::factory()->racemanager()->create();

        $race = Race::factory()->create();

        $category = Category::factory()->recycle($race->championship)->create();

        $response = Cache::lock('participant:100', 10)->get(function () use ($race, $user, $category) {

            return $this->actingAs($user)
                ->from(route('races.participants.create', $race))
                ->post(route('races.participants.store', $race), [
                    'bib' => 100,
                    'category' => $category->ulid,
                    ...$this->generateValidDriver(),
                    ...$this->generateValidCompetitor(),
                    ...$this->generateValidMechanic(),
                    ...$this->generateValidVehicle(),
                    'consent_privacy' => true,
                    'bonus' => 'false',
                ]);

        });

        $response->assertRedirectToRoute('races.participants.create', $race);

        $response->assertSessionHasErrors('bib');

        $this->assertEquals(0, Participant::where('bib', 100)->count());

    }

    public function test_participant_can_register_to_more_races()
    {
        $user = User::factory()->racemanager()->create();

        $championship = Championship::factory()
            ->has(Race::factory()->count(2))
            ->create();

        $category = Category::factory()->recycle($championship)->create();

        [$pastRace, $race] = $championship->races;

        $existingParticipant = Participant::factory()->category($category)->create([
            'bib' => 100,
            'championship_id' => $championship->getKey(),
            'race_id' => $pastRace->getKey(),
            'driver_licence' => hash('sha512', 'D0001'),
            'driver' => [
                'licence_number' => 'D0001',
            ],
        ]);

        $response = $this->actingAs($user)
            ->from(route('races.participants.create', $race))
            ->post(route('races.participants.store', $race), [
                'bib' => 100,
                'category' => $category->ulid,
                ...$this->generateValidDriver(),
                'driver_licence_number' => $existingParticipant->driver['licence_number'],
                ...$this->generateValidVehicle(),
                'consent_privacy' => true,
                'bonus' => 'false',
            ]);

        $response->assertRedirectToRoute('races.participants.index', $race);

        $response->assertSessionHasNoErrors();

        $response->assertSessionHas('flash.banner', '100 John Racer added.');

        $participant = $race->participants()->first();

        $this->assertInstanceOf(Participant::class, $participant);

        $this->assertEquals(100, $participant->bib);
        $this->assertEquals($category->ulid, $participant->category);
        $this->assertEquals('John', $participant->first_name);
        $this->assertEquals('Racer', $participant->last_name);
    }

    public function test_participant_can_be_updated()
    {
        config(['races.registration.form' => 'complete']);

        $user = User::factory()->racemanager()->create();

        $championship = Championship::factory()
            ->has(Race::factory()->count(2))
            ->create();

        $category = Category::factory()->recycle($championship)->create();

        [$pastRace, $race] = $championship->races;

        $existingParticipant = Participant::factory()->category($category)->create([
            'bib' => 100,
            'championship_id' => $championship->getKey(),
            'race_id' => $race->getKey(),
            'driver_licence' => hash('sha512', 'D0001'),
            'driver' => [
                'licence_number' => 'D0001',
            ],
        ]);

        $response = $this->actingAs($user)
            ->from(route('participants.edit', $existingParticipant))
            ->put(route('participants.update', $existingParticipant), [
                'bib' => 100,
                'category' => $category->ulid,
                ...$this->generateValidDriver(),
                'driver_licence_number' => $existingParticipant->driver['licence_number'],
                ...$this->generateValidVehicle(),
                'consent_privacy' => true,
            ]);

        $response->assertRedirectToRoute('races.participants.index', $race);

        $response->assertSessionHasNoErrors();

        $response->assertSessionHas('flash.banner', '100 John Racer updated.');

        $participant = $race->participants()->first();

        $this->assertInstanceOf(Participant::class, $participant);

        $this->assertEquals(100, $participant->bib);
        $this->assertEquals($category->ulid, $participant->category);
        $this->assertTrue($participant->racingCategory->is($category));
        $this->assertEquals('John', $participant->first_name);
        $this->assertEquals('Racer', $participant->last_name);
    }

    public function test_participant_can_be_deleted()
    {
        config(['races.registration.form' => 'complete']);

        $user = User::factory()->racemanager()->create();

        $championship = Championship::factory()
            ->has(Race::factory()->count(2))
            ->create();

        $category = Category::factory()->recycle($championship)->create();

        [$pastRace, $race] = $championship->races;

        $existingParticipant = Participant::factory()->category($category)->create([
            'bib' => 100,
            'championship_id' => $championship->getKey(),
            'race_id' => $race->getKey(),
            'driver_licence' => hash('sha512', 'D0001'),
            'driver' => [
                'licence_number' => 'D0001',
            ],
        ]);

        $response = $this->actingAs($user)
            ->from(route('participants.edit', $existingParticipant))
            ->delete(route('participants.destroy', $existingParticipant));

        $response->assertRedirectToRoute('races.participants.index', $race);

        $response->assertSessionHasNoErrors();

        $response->assertSessionHas('flash.banner', "100 {$existingParticipant->first_name} {$existingParticipant->last_name} removed.");

        $this->assertEquals(0, $race->participants()->count());

        $trashedParticipant = TrashedParticipant::first();

        $this->assertInstanceOf(TrashedParticipant::class, $trashedParticipant);

        $this->assertEquals(100, $trashedParticipant->bib);
        $this->assertEquals($category->ulid, $trashedParticipant->category);
        $this->assertTrue($trashedParticipant->racingCategory->is($category));
        $this->assertTrue($trashedParticipant->race->is($race));
        $this->assertTrue($trashedParticipant->championship->is($championship));
        $this->assertEquals($existingParticipant->first_name, $trashedParticipant->first_name);
        $this->assertEquals($existingParticipant->last_name, $trashedParticipant->last_name);
    }

    public function test_participant_not_deleted_if_user_lacks_permissions()
    {
        config(['races.registration.form' => 'complete']);

        $user = User::factory()->timekeeper()->create();

        $championship = Championship::factory()
            ->has(Race::factory()->count(2))
            ->create();

        $category = Category::factory()->recycle($championship)->create();

        [$pastRace, $race] = $championship->races;

        $existingParticipant = Participant::factory()->category($category)->create([
            'bib' => 100,
            'championship_id' => $championship->getKey(),
            'race_id' => $race->getKey(),
            'driver_licence' => hash('sha512', 'D0001'),
            'driver' => [
                'licence_number' => 'D0001',
            ],
        ]);

        $response = $this->actingAs($user)
            ->from(route('participants.edit', $existingParticipant))
            ->delete(route('participants.destroy', $existingParticipant));

        $response->assertForbidden();
    }
}
