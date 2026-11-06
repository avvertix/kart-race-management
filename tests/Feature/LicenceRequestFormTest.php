<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LicenceDocument;
use App\Enums\LicenceProvider;
use App\Enums\LicenceRequestPaymentStatus;
use App\Enums\LicenceRequestSection;
use App\Enums\LicenceRequestStatus;
use App\Livewire\LicenceRequestForm;
use App\Models\LicenceRequest;
use App\Models\LicenceRequestChangeRequest;
use App\Models\LicenceRequestDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use ReflectionClass;
use ReflectionMethod;
use Tests\EnablesLicencesAndEnrollments;
use Tests\TestCase;

class LicenceRequestFormTest extends TestCase
{
    use EnablesLicencesAndEnrollments;
    use FastRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('licence-documents');
        Storage::fake('payments');
    }

    public function test_other_users_cannot_open_the_request(): void
    {
        $licenceRequest = LicenceRequest::factory()->withLicenceAndHolder()->create();
        $document = LicenceRequestDocument::factory()->for($licenceRequest)->create();

        $other = User::factory()->create();

        $this->actingAs($other)->get(route('licence-requests.show', $licenceRequest))->assertForbidden();
        $this->actingAs($other)->get(route('licence-requests.edit', $licenceRequest))->assertForbidden();
        $this->actingAs($other)->get(route('licence-requests.documents.show', [$licenceRequest, $document]))->assertForbidden();
        $this->actingAs($other)->get(route('licence-requests.forms.show', [$licenceRequest, LicenceDocument::Privacy]))->assertForbidden();
        $this->actingAs($other)->delete(route('licence-requests.destroy', $licenceRequest))->assertForbidden();
        $this->actingAs($other)
            ->post(route('licence-requests.payment.store', $licenceRequest), ['proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf')])
            ->assertForbidden();

        $this->assertModelExists($licenceRequest);
    }

    public function test_each_section_saves_on_its_own_and_the_draft_reloads(): void
    {
        $user = User::factory()->create();
        $licenceRequest = LicenceRequest::factory()->for($user)->create();

        $this->actingAs($user)->get(route('licence-requests.edit', $licenceRequest))->assertSuccessful();

        Livewire::actingAs($user)
            ->test(LicenceRequestForm::class, ['licenceRequest' => $licenceRequest])
            ->assertSet('openSection', 'licence')
            ->set('provider', 'aci')
            ->set('licenceType', 'int-e')
            ->set('previousLicenceNumber', 'A123456')
            ->call('saveLicence')
            ->assertHasNoErrors()
            ->assertSet('openSection', 'holder');

        $licenceRequest->refresh();

        $this->assertEquals(LicenceProvider::Aci, $licenceRequest->provider);
        $this->assertEquals('int-e', $licenceRequest->licence_type);
        $this->assertTrue($licenceRequest->includes_competitor);
        $this->assertEquals('A123456', $licenceRequest->previous_licence_number);
        $this->assertNull($licenceRequest->holder);

        Livewire::actingAs($user)
            ->test(LicenceRequestForm::class, ['licenceRequest' => $licenceRequest])
            ->assertSet('openSection', 'holder')
            ->assertSet('licenceType', 'int-e')
            ->set('holder', $this->holderInput())
            ->call('saveHolder')
            ->assertHasNoErrors()
            ->assertDontSee(LicenceRequestSection::Parents->label());

        $this->assertEquals('Giulia Bianchi', $licenceRequest->fresh()->holder->fullName());
    }

    public function test_licence_type_must_belong_to_the_provider(): void
    {
        $user = User::factory()->create();
        $licenceRequest = LicenceRequest::factory()->for($user)->create();

        Livewire::actingAs($user)
            ->test(LicenceRequestForm::class, ['licenceRequest' => $licenceRequest])
            ->set('provider', 'asi')
            ->set('licenceType', 'naz-e')
            ->set('previousLicenceNumber', '')
            ->call('saveLicence')
            ->assertHasErrors(['licenceType', 'previousLicenceNumber']);

        $this->assertNull($licenceRequest->fresh()->licence_type);
    }

    public function test_competitor_licence_is_dropped_for_types_that_cannot_include_it(): void
    {
        $user = User::factory()->create();
        $licenceRequest = LicenceRequest::factory()->for($user)->create();

        Livewire::actingAs($user)
            ->test(LicenceRequestForm::class, ['licenceRequest' => $licenceRequest])
            ->set('provider', 'asi')
            ->set('licenceType', 'ma')
            ->set('previousLicenceNumber', 'ASI-1')
            ->call('saveLicence')
            ->assertHasNoErrors();

        $this->assertFalse($licenceRequest->fresh()->includes_competitor);
    }

    public function test_aci_driver_licence_for_the_driver_only_excludes_the_competitor_licence(): void
    {
        $user = User::factory()->create();
        $licenceRequest = LicenceRequest::factory()->for($user)->create();

        Livewire::actingAs($user)
            ->test(LicenceRequestForm::class, ['licenceRequest' => $licenceRequest])
            ->set('provider', 'aci')
            ->set('licenceType', 'naz-e')
            ->set('driverOnly', true)
            ->set('previousLicenceNumber', 'A123456')
            ->call('saveLicence')
            ->assertHasNoErrors();

        $this->assertFalse($licenceRequest->fresh()->includes_competitor);

        Livewire::actingAs($user)
            ->test(LicenceRequestForm::class, ['licenceRequest' => $licenceRequest->fresh()])
            ->assertSet('driverOnly', true);
    }

    public function test_invalid_section_is_not_saved(): void
    {
        $user = User::factory()->create();
        $licenceRequest = LicenceRequest::factory()->for($user)->create();

        Livewire::actingAs($user)
            ->test(LicenceRequestForm::class, ['licenceRequest' => $licenceRequest])
            ->set('holder', [...$this->holderInput(), 'email' => 'not-an-email'])
            ->call('saveHolder')
            ->assertHasErrors('holder.email');

        $this->assertNull($licenceRequest->fresh()->holder);
    }

    public function test_underage_holder_needs_a_parent(): void
    {
        $user = User::factory()->create();
        $licenceRequest = LicenceRequest::factory()->for($user)->create();

        $component = Livewire::actingAs($user)
            ->test(LicenceRequestForm::class, ['licenceRequest' => $licenceRequest])
            ->set('holder', $this->holderInput(now()->subYears(12)->toDateString()))
            ->call('saveHolder')
            ->assertHasNoErrors()
            ->assertSee(LicenceRequestSection::Parents->label())
            ->call('saveParents')
            ->assertHasErrors('parents');

        $component
            ->call('addParent')
            ->set('parents.0', [...$this->holderInput(), 'first_name' => 'Marco'])
            ->call('saveParents')
            ->assertHasNoErrors();

        $this->assertEquals(['Marco Bianchi'], array_map(fn ($parent) => $parent->fullName(), $licenceRequest->fresh()->parentHolders()));
    }

    public function test_documents_are_uploaded_from_the_form(): void
    {
        $user = User::factory()->create();
        $licenceRequest = LicenceRequest::factory()->withLicenceAndHolder()->for($user)->create();

        Livewire::actingAs($user)
            ->test(LicenceRequestForm::class, ['licenceRequest' => $licenceRequest])
            ->call('open', 'documents')
            ->assertSee('Medical certificate')
            ->set('files.medical_certificate', UploadedFile::fake()->create('certificate.pdf', 50, 'application/pdf'))
            ->assertHasNoErrors()
            ->assertSee('certificate.pdf');

        $upload = $licenceRequest->documents()->sole();

        $this->assertEquals(LicenceDocument::MedicalCertificate, $upload->document);
        Storage::disk('licence-documents')->assertExists($upload->path);

        $this->actingAs($user)->get(route('licence-requests.documents.show', [$licenceRequest, $upload]))->assertSuccessful();
    }

    public function test_actions_do_not_shadow_livewire_client_methods(): void
    {
        $reserved = ['upload', 'uploadMultiple', 'removeUpload', 'cancelUpload', 'set', 'get', 'call', 'watch', 'entangle', 'refresh', 'commit', 'dispatch', 'on'];

        $actions = collect((new ReflectionClass(LicenceRequestForm::class))->getMethods(ReflectionMethod::IS_PUBLIC))
            ->filter(fn (ReflectionMethod $method) => $method->class === LicenceRequestForm::class)
            ->map(fn (ReflectionMethod $method) => $method->getName())
            ->all();

        $this->assertEmpty(array_intersect($reserved, $actions), 'wire:click on these names calls the Livewire client method instead of the action.');
    }

    public function test_payment_comes_after_the_review_not_in_the_form(): void
    {
        $user = User::factory()->create();
        $licenceRequest = LicenceRequest::factory()->withLicenceAndHolder()->withAllDocuments()->for($user)->create();

        Livewire::actingAs($user)
            ->test(LicenceRequestForm::class, ['licenceRequest' => $licenceRequest])
            ->assertSet('openSection', 'review')
            ->assertSee('After you submit, the organizer checks the request and tells you the amount to pay.')
            ->assertDontSee(__('Upload payment proof'));

        $this->actingAs($user)
            ->get(route('licence-requests.show', $licenceRequest))
            ->assertSee('The organizer sets the amount after checking your request.');
    }

    public function test_proof_is_uploaded_from_the_request_page_once_verified(): void
    {
        $user = User::factory()->create();
        $licenceRequest = LicenceRequest::factory()->verified()->awaitingPayment(5000)->for($user)->create();

        $this->actingAs($user)
            ->get(route('licence-requests.show', $licenceRequest))
            ->assertSuccessful()
            ->assertSee('Your request is verified')
            ->assertSee('Pay €50.00 so the organizer can request your licence.')
            ->assertSee(route('licence-requests.payment.store', $licenceRequest));

        $this->actingAs($user)
            ->post(route('licence-requests.payment.store', $licenceRequest), ['proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf')])
            ->assertRedirect(route('licence-requests.show', $licenceRequest))
            ->assertSessionHasNoErrors();

        $this->assertEquals(LicenceRequestPaymentStatus::PaymentSubmitted, $licenceRequest->fresh()->payment_status);
        $this->assertEquals(1, $licenceRequest->payments()->count());
    }

    public function test_complete_request_is_submitted_unpaid_with_the_declaration(): void
    {
        $user = User::factory()->create();
        $licenceRequest = LicenceRequest::factory()->withLicenceAndHolder()->withAllDocuments()->for($user)->create();

        Livewire::actingAs($user)
            ->test(LicenceRequestForm::class, ['licenceRequest' => $licenceRequest])
            ->assertSet('openSection', 'review')
            ->call('submit')
            ->assertHasErrors('declared')
            ->set('declared', true)
            ->call('submit')
            ->assertRedirect(route('licence-requests.show', $licenceRequest));

        $this->assertEquals(LicenceRequestStatus::Submitted, $licenceRequest->fresh()->status);

        $this->actingAs($user)->get(route('licence-requests.edit', $licenceRequest))->assertForbidden();
        $this->actingAs($user)->get(route('licence-requests.show', $licenceRequest))->assertSuccessful()->assertSee(LicenceRequestStatus::Submitted->label());
    }

    public function test_after_a_change_request_only_flagged_parts_can_change(): void
    {
        $user = User::factory()->create();
        $licenceRequest = LicenceRequest::factory()->changesRequested()->withAllDocuments()->for($user)->create();
        LicenceRequestChangeRequest::factory()->for($licenceRequest)->create(['section' => LicenceRequestSection::Holder, 'note' => 'Birth place is wrong.']);

        $this->actingAs($user)->get(route('licence-requests.show', $licenceRequest))->assertSee('Birth place is wrong.');

        Livewire::actingAs($user)
            ->test(LicenceRequestForm::class, ['licenceRequest' => $licenceRequest])
            ->assertSet('openSection', 'holder')
            ->assertSee('Birth place is wrong.')
            ->call('submit')
            ->assertHasErrors('request');

        Livewire::actingAs($user)
            ->test(LicenceRequestForm::class, ['licenceRequest' => $licenceRequest])
            ->set('licenceType', 'int-e')
            ->call('saveLicence')
            ->assertForbidden();

        $this->assertEquals(LicenceRequestStatus::ChangesRequested, $licenceRequest->fresh()->status);

        Livewire::actingAs($user)
            ->test(LicenceRequestForm::class, ['licenceRequest' => $licenceRequest->fresh()])
            ->set('holder.birth_place', 'Brescia')
            ->call('saveHolder')
            ->assertHasNoErrors()
            ->assertSet('openSection', 'review')
            ->call('submit')
            ->assertRedirect(route('licence-requests.show', $licenceRequest));

        $licenceRequest->refresh();

        $this->assertEquals(LicenceRequestStatus::Submitted, $licenceRequest->status);
        $this->assertEquals('naz-e', $licenceRequest->licence_type);
        $this->assertEquals('Brescia', $licenceRequest->holder->birth_place);
    }

    public function test_moving_on_does_not_resolve_a_change_request_but_uploading_does(): void
    {
        $user = User::factory()->create();
        $licenceRequest = LicenceRequest::factory()->changesRequested()->for($user)->create();
        $changeRequest = LicenceRequestChangeRequest::factory()->for($licenceRequest)->create(['section' => LicenceRequestSection::Documents, 'note' => 'Add the medical certificate.']);

        Livewire::actingAs($user)
            ->test(LicenceRequestForm::class, ['licenceRequest' => $licenceRequest])
            ->call('continue', 'documents');

        $this->assertNull($changeRequest->fresh()->resolved_at);

        Livewire::actingAs($user)
            ->test(LicenceRequestForm::class, ['licenceRequest' => $licenceRequest])
            ->call('continue', 'holder')
            ->assertNotFound();

        Livewire::actingAs($user)
            ->test(LicenceRequestForm::class, ['licenceRequest' => $licenceRequest])
            ->set('files.medical_certificate', UploadedFile::fake()->create('certificate.pdf', 50, 'application/pdf'))
            ->assertHasNoErrors();

        $this->assertNotNull($changeRequest->fresh()->resolved_at);
    }

    public function test_fixing_the_holder_into_an_underage_one_opens_the_parents(): void
    {
        $user = User::factory()->create();
        $licenceRequest = LicenceRequest::factory()->changesRequested()->for($user)->create();
        LicenceRequestChangeRequest::factory()->for($licenceRequest)->create(['section' => LicenceRequestSection::Holder, 'note' => 'Wrong birth date.']);

        Livewire::actingAs($user)
            ->test(LicenceRequestForm::class, ['licenceRequest' => $licenceRequest])
            ->set('holder.birth_date', now()->subYears(12)->toDateString())
            ->call('saveHolder')
            ->assertHasNoErrors()
            ->assertSet('openSection', 'parents')
            ->call('addParent')
            ->set('parents.0', [...$this->holderInput(), 'first_name' => 'Marco'])
            ->call('saveParents')
            ->assertHasNoErrors()
            ->assertSet('openSection', 'documents');
    }

    public function test_removing_the_second_parent_removes_their_documents(): void
    {
        $user = User::factory()->create();
        $licenceRequest = LicenceRequest::factory()->underage()->for($user)->create();

        $component = Livewire::actingAs($user)
            ->test(LicenceRequestForm::class, ['licenceRequest' => $licenceRequest])
            ->call('addParent')
            ->set('parents.1', [...$this->holderInput(), 'first_name' => 'Anna'])
            ->call('saveParents')
            ->assertHasNoErrors()
            ->set('files.identity_document-1', UploadedFile::fake()->create('anna.pdf', 50, 'application/pdf'))
            ->assertHasNoErrors();

        $upload = $licenceRequest->documents()->sole();

        $component
            ->call('removeParent', 1)
            ->call('saveParents')
            ->assertHasNoErrors();

        $this->assertModelMissing($upload);
        Storage::disk('licence-documents')->assertMissing($upload->path);
    }

    public function test_pre_filled_form_downloads_as_pdf(): void
    {
        config(['licences.forms' => []]);

        $user = User::factory()->create();
        $licenceRequest = LicenceRequest::factory()->withLicenceAndHolder()->for($user)->create();

        $this->actingAs($user)
            ->get(route('licence-requests.forms.show', [$licenceRequest, LicenceDocument::Privacy]))
            ->assertSuccessful()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->actingAs($user)
            ->get(route('licence-requests.forms.show', [$licenceRequest, LicenceDocument::ParentalAuthorisation]))
            ->assertNotFound();

        $this->actingAs($user)
            ->get(route('licence-requests.forms.show', [$licenceRequest, LicenceDocument::MedicalCertificate]))
            ->assertNotFound();
    }

    public function test_a_parent_identity_document_is_asked_for_before_the_parents_are_entered(): void
    {
        $user = User::factory()->create();
        $licenceRequest = LicenceRequest::factory()->underage()->for($user)->create(['parents' => []]);

        $component = Livewire::actingAs($user)
            ->test(LicenceRequestForm::class, ['licenceRequest' => $licenceRequest])
            ->call('open', LicenceRequestSection::Documents->value)
            ->assertSee('Parent or guardian 1')
            ->set('files.identity_document-0', UploadedFile::fake()->create('parent.pdf', 50, 'application/pdf'))
            ->assertHasNoErrors();

        $upload = $licenceRequest->documents()->sole();

        $component
            ->call('addParent')
            ->set('parents.0', [...$this->holderInput(), 'first_name' => 'Marco'])
            ->call('saveParents')
            ->assertHasNoErrors()
            ->call('open', LicenceRequestSection::Documents->value)
            ->assertSee('Marco');

        $this->assertModelExists($upload);
    }

    public function test_a_form_published_by_the_provider_is_downloaded_from_its_website(): void
    {
        config(['licences.forms.aci.privacy' => 'https://example.com/aci-privacy.pdf']);

        $user = User::factory()->create();
        $licenceRequest = LicenceRequest::factory()->withLicenceAndHolder()->for($user)->create();

        Livewire::actingAs($user)
            ->test(LicenceRequestForm::class, ['licenceRequest' => $licenceRequest])
            ->call('open', LicenceRequestSection::Forms->value)
            ->assertSeeHtml('href="https://example.com/aci-privacy.pdf"')
            ->assertSee('Download from ACI Sport')
            ->assertSee('Opens the provider\'s website. Fill it in by hand.')
            ->assertSee('Download pre-filled');

        $this->actingAs($user)
            ->get(route('licence-requests.forms.show', [$licenceRequest, LicenceDocument::Privacy]))
            ->assertRedirect('https://example.com/aci-privacy.pdf');

        $this->actingAs($user)
            ->get(route('licence-requests.forms.show', [$licenceRequest, LicenceDocument::Delegation]))
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_requester_deletes_a_draft(): void
    {
        $user = User::factory()->create();
        $licenceRequest = LicenceRequest::factory()->for($user)->create();

        $this->actingAs($user)
            ->get(route('my-licences.index'))
            ->assertSee(route('licence-requests.show', $licenceRequest));

        $this->actingAs($user)
            ->delete(route('licence-requests.destroy', $licenceRequest))
            ->assertRedirect(route('my-licences.index'));

        $this->assertModelMissing($licenceRequest);
    }

    /**
     * @return array<string, mixed>
     */
    private function holderInput(string $birthDate = '1990-04-02'): array
    {
        return [
            'first_name' => 'Giulia',
            'last_name' => 'Bianchi',
            'sex' => '20',
            'birth_date' => $birthDate,
            'birth_place' => 'Brescia',
            'fiscal_code' => '',
            'nationality' => 'Italian',
            'email' => 'giulia@example.com',
            'phone' => '+39 333 7654321',
            'residence_address' => [
                'address' => 'Via Milano 3',
                'city' => 'Brescia',
                'province' => 'BS',
                'postal_code' => '25121',
            ],
        ];
    }
}
