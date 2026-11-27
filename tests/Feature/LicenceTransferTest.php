<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Data\LicenceSettingsData;
use App\Enums\LicenceProvider;
use App\Enums\LicenceSource;
use App\Livewire\LinkedDrivers;
use App\Models\Championship;
use App\Models\DriverLicence;
use App\Models\Licence;
use App\Models\Participant;
use App\Models\Sex;
use App\Models\User;
use Livewire\Livewire;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\EnablesLicencesAndEnrollments;
use Tests\TestCase;

class LicenceTransferTest extends TestCase
{
    use EnablesLicencesAndEnrollments;
    use FastRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['licences.enabled_types' => []]);
    }

    public function test_a_confirmed_participation_becomes_a_verified_licence_when_one_type_matches(): void
    {
        config(['licences.enabled_types' => ['asi.lkm']]);

        $user = User::factory()->create();
        $organizer = User::factory()->organizer()->create();
        $participant = $this->participation($user, LicenceProvider::Asi, year: 2025, number: 'ASI-123');

        $this->actingAs($organizer);
        $participant->update(['confirmed_at' => now()->subMonth()]);

        $this->actingAs($user)
            ->post(route('my-licences.transfers.store', $participant))
            ->assertRedirect();

        $licence = Licence::query()->sole();

        $this->assertSame($user->getKey(), $licence->user_id);
        $this->assertSame(LicenceProvider::Asi, $licence->provider);
        $this->assertSame('lkm', $licence->licence_type);
        $this->assertSame('ASI-123', $licence->number);
        $this->assertSame(2025, $licence->validity_year);
        $this->assertSame(LicenceSource::Participation, $licence->source);
        $this->assertTrue($licence->isVerifiedOrEmitted());
        $this->assertSame($organizer->getKey(), $licence->verified_by);
        $this->assertSame('Mario', $licence->holder->first_name);
        $this->assertSame('2004-05-14', $licence->holder->birth_date);
    }

    public function test_the_form_opens_when_several_licence_types_match(): void
    {
        $user = User::factory()->create();
        $participant = $this->participation($user, LicenceProvider::Aci, year: 2025, number: 'ACI-456', confirmed: true);

        $this->actingAs($user)
            ->post(route('my-licences.transfers.store', $participant))
            ->assertRedirectToRoute('my-licences.transfers.create', $participant);

        $this->assertDatabaseCount('licences', 0);

        $this->actingAs($user)
            ->get(route('my-licences.transfers.create', $participant))
            ->assertOk()
            ->assertSee('ACI-456')
            ->assertSee('2025')
            ->assertSee('Mario');

        $holder = [
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'sex' => Sex::MALE->value,
            'birth_date' => '2004-05-14',
            'birth_place' => 'Bergamo',
            'fiscal_code' => null,
            'nationality' => 'Italy',
            'email' => 'mario@example.com',
            'phone' => '+39 333 1234567',
            'residence_address' => ['address' => 'Via Roma 1', 'city' => 'Bergamo', 'province' => 'BG', 'postal_code' => '24121'],
        ];

        $this->actingAs($user)
            ->post(route('my-licences.transfers.store', $participant), ['provider' => 'aci', 'licence_type' => 'naz-f', 'holder' => $holder])
            ->assertRedirect();

        $licence = Licence::query()->sole();

        $this->assertSame('naz-f', $licence->licence_type);
        $this->assertSame('ACI-456', $licence->number);
        $this->assertSame(2025, $licence->validity_year);
        $this->assertTrue($licence->isVerified());
    }

    public function test_only_the_latest_confirmed_participation_of_a_local_licence_can_be_transferred(): void
    {
        $user = User::factory()->create();

        $unconfirmed = $this->participation($user, LicenceProvider::Aci, year: 2025, number: 'UNCONFIRMED');
        $foreign = $this->participation($user, LicenceProvider::Aci, year: 2025, number: 'FOREIGN', confirmed: true, category: DriverLicence::FOREIGN);
        $someoneElse = $this->participation(User::factory()->create(), LicenceProvider::Aci, year: 2025, number: 'OTHER', confirmed: true);
        $older = $this->participation($user, LicenceProvider::Aci, year: 2024, number: 'SAME', confirmed: true);
        $latest = $this->participation($user, LicenceProvider::Aci, year: 2025, number: 'SAME', confirmed: true);

        foreach ([$unconfirmed, $foreign, $someoneElse] as $participant) {
            $this->actingAs($user)->get(route('my-licences.transfers.create', $participant))->assertNotFound();
        }

        $this->actingAs($user)
            ->get(route('my-licences.transfers.create', $older))
            ->assertOk()
            ->assertSee($latest->championship->title);

        Licence::factory()->create(['number' => 'SAME', 'validity_year' => 2025]);

        $this->actingAs($user)->get(route('my-licences.transfers.create', $older))->assertNotFound();
    }

    public function test_a_licence_already_added_for_a_later_year_is_not_offered_again(): void
    {
        $user = User::factory()->create();
        $participant = $this->participation($user, LicenceProvider::Aci, year: 2025, number: 'RENEWED', confirmed: true);

        Licence::factory()->for($user)->create(['number' => 'RENEWED', 'validity_year' => 2026]);

        $this->actingAs($user)->get(route('my-licences.transfers.create', $participant))->assertNotFound();
        $this->actingAs($user)->post(route('my-licences.transfers.store', $participant))->assertNotFound();
        $this->actingAs($user)->get(route('my-licences.index'))->assertDontSee('From your races');
    }

    public function test_linked_drivers_offer_to_add_the_licence(): void
    {
        $user = User::factory()->create();
        $participant = $this->participation($user, LicenceProvider::Aci, year: 2025, number: 'LINKED', confirmed: true);

        Livewire::actingAs($user)
            ->test(LinkedDrivers::class)
            ->assertSee('Add to my licences')
            ->assertSeeHtml(route('my-licences.transfers.store', $participant));

        $this->actingAs($user)
            ->get(route('my-licences.index'))
            ->assertSee('From your races')
            ->assertSee(route('my-licences.transfers.store', $participant));

        Licence::factory()->create(['number' => 'LINKED', 'validity_year' => 2025]);

        Livewire::actingAs($user)
            ->test(LinkedDrivers::class)
            ->assertDontSee('Add to my licences');
    }

    private function participation(User $user, LicenceProvider $provider, int $year, string $number, bool $confirmed = false, DriverLicence $category = DriverLicence::LOCAL_NATIONAL): Participant
    {
        $championship = Championship::factory()->create([
            'start_at' => "{$year}-03-01",
            'licences' => new LicenceSettingsData(provider: $provider),
        ]);

        return Participant::factory()
            ->for($championship)
            ->driver([
                'first_name' => 'Mario',
                'last_name' => 'Rossi',
                'licence_number' => $number,
                'licence_type' => $category,
                'sex' => Sex::MALE,
                'birth_date' => '2004-05-14',
                'birth_place' => 'Bergamo',
                'fiscal_code' => null,
                'email' => 'mario@example.com',
                'phone' => '+39 333 1234567',
                'nationality' => 'Italy',
                'residence_address' => ['address' => 'Via Roma 1', 'city' => 'Bergamo', 'province' => 'BG', 'postal_code' => '24121'],
            ])
            ->create([
                'claimed_by' => $user->getKey(),
                'confirmed_at' => $confirmed ? now() : null,
            ]);
    }
}
