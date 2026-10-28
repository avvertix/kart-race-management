<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\ParticipantListing;
use App\Models\DriverLicence;
use App\Models\FederationAuthorization;
use App\Models\Participant;
use App\Models\Race;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class ParticipantFederationAuthorizationControllerTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_foreign_participant_can_upload_federation_authorization()
    {
        Storage::fake('federation-authorizations');

        $participant = Participant::factory()
            ->for(Race::factory())
            ->create(['licence_type' => DriverLicence::FOREIGN]);

        $file = UploadedFile::fake()->create('authorization.pdf', 100, 'application/pdf');

        $response = $this
            ->from(route('registration.show', $participant))
            ->post(URL::signedRoute('federation-authorization.store', $participant->signedUrlParameters()), [
                'participant' => $participant->uuid,
                'authorization' => $file,
            ]);

        $response->assertRedirect($participant->qrCodeUrl());

        $response->assertSessionHas('status', 'federation-authorization-uploaded');

        Storage::disk('federation-authorizations')->assertExists($file->hashName());

        $this->assertEquals($file->hashName(), $participant->federationAuthorizations()->sole()->path);
    }

    public function test_federation_authorization_rejected_for_non_foreign_licence()
    {
        Storage::fake('federation-authorizations');

        $participant = Participant::factory()
            ->for(Race::factory())
            ->create(['licence_type' => DriverLicence::LOCAL_NATIONAL]);

        $file = UploadedFile::fake()->image('authorization.jpg', 200, 200);

        $response = $this
            ->from(route('registration.show', $participant))
            ->post(URL::signedRoute('federation-authorization.store', $participant->signedUrlParameters()), [
                'participant' => $participant->uuid,
                'authorization' => $file,
            ]);

        $response->assertSessionHasErrors('authorization');

        Storage::disk('federation-authorizations')->assertMissing($file->hashName());

        $this->assertFalse($participant->federationAuthorizations()->exists());
    }

    public function test_federation_authorization_rejects_unsupported_file_types()
    {
        Storage::fake('federation-authorizations');

        $participant = Participant::factory()
            ->for(Race::factory())
            ->create(['licence_type' => DriverLicence::FOREIGN]);

        $response = $this
            ->from(route('registration.show', $participant))
            ->post(URL::signedRoute('federation-authorization.store', $participant->signedUrlParameters()), [
                'participant' => $participant->uuid,
                'authorization' => UploadedFile::fake()->create('authorization.docx', 100),
            ]);

        $response->assertSessionHasErrors('authorization');

        $this->assertFalse($participant->federationAuthorizations()->exists());
    }

    public function test_federation_authorization_not_uploaded_without_signature()
    {
        $participant = Participant::factory()
            ->for(Race::factory())
            ->create(['licence_type' => DriverLicence::FOREIGN]);

        $response = $this
            ->post(route('federation-authorization.store'), [
                'participant' => $participant->uuid,
            ]);

        $response->assertForbidden();
    }

    public function test_federation_authorization_downloadable()
    {
        Storage::fake('federation-authorizations');

        $authorization = FederationAuthorization::factory()
            ->for(Participant::factory()->for(Race::factory())->state(['licence_type' => DriverLicence::FOREIGN]))
            ->create(['path' => 'authorization.jpg']);

        Storage::disk('federation-authorizations')->putFileAs('', UploadedFile::fake()->image('authorization.jpg'), 'authorization.jpg');

        $response = $this->get($authorization->download_url);

        $response->assertSuccessful();

        $response->assertHeader('content-type', 'image/jpeg');
    }

    public function test_organizer_sees_federation_authorization_status()
    {
        $user = User::factory()->organizer()->create();

        $race = Race::factory()->create();

        $withAuthorization = Participant::factory()->for($race)->recycle($race->championship)->category()
            ->has(FederationAuthorization::factory())
            ->create(['licence_type' => DriverLicence::FOREIGN]);

        $withoutAuthorization = Participant::factory()->for($race)->recycle($race->championship)->category()
            ->create(['licence_type' => DriverLicence::FOREIGN]);

        $this->actingAs($user)
            ->get(route('participants.show', $withAuthorization))
            ->assertOk()
            ->assertSee('Authorization uploaded on')
            ->assertDontSee('Federation authorization not uploaded');

        $this->actingAs($user)
            ->get(route('participants.show', $withoutAuthorization))
            ->assertOk()
            ->assertSee('Federation authorization not uploaded');
    }

    public function test_race_manager_sees_federation_authorization_in_participant_listing()
    {
        $race = Race::factory()->create();

        $participant = Participant::factory()->for($race)->recycle($race->championship)->category()
            ->has(FederationAuthorization::factory())
            ->create(['licence_type' => DriverLicence::FOREIGN]);

        Livewire::actingAs(User::factory()->racemanager()->create())
            ->test(ParticipantListing::class, ['race' => $race])
            ->call('select', $participant->getKey())
            ->assertSee('Authorization uploaded on')
            ->assertSee(route('federation-authorization.show', $participant->federationAuthorizations->first()));
    }

    public function test_federation_authorization_hidden_in_participant_listing_for_timekeeper()
    {
        $race = Race::factory()->create();

        $participant = Participant::factory()->for($race)->recycle($race->championship)->category()
            ->has(FederationAuthorization::factory())
            ->create(['licence_type' => DriverLicence::FOREIGN]);

        Livewire::actingAs(User::factory()->timekeeper()->create())
            ->test(ParticipantListing::class, ['race' => $race])
            ->call('select', $participant->getKey())
            ->assertDontSee('Authorization uploaded on');
    }

    public function test_upload_form_shown_only_to_foreign_licence_holders()
    {
        $race = Race::factory()->create();

        $foreignParticipant = Participant::factory()->for($race)->recycle($race->championship)->category()->create(['licence_type' => DriverLicence::FOREIGN]);

        $localParticipant = Participant::factory()->for($race)->recycle($race->championship)->category()->create(['licence_type' => DriverLicence::LOCAL_NATIONAL]);

        $this->get(URL::signedRoute('registration.show', ['registration' => $foreignParticipant, 'p' => $foreignParticipant->signatureContent()]))
            ->assertOk()
            ->assertSee('Federation authorization')
            ->assertSee(route('federation-authorization.store'), false);

        $this->get(URL::signedRoute('registration.show', ['registration' => $localParticipant, 'p' => $localParticipant->signatureContent()]))
            ->assertOk()
            ->assertDontSee('Federation authorization')
            ->assertDontSee(route('federation-authorization.store'), false);
    }
}
