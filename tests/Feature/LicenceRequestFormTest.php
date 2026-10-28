<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\LicenceRequests\StartLicenceRequest;
use App\Enums\LicenceDocumentKind;
use App\Enums\LicenceFormTemplate;
use App\Enums\LicenceLevel;
use App\Enums\LicenceRequestSection;
use App\Enums\LicenceRequestStatus;
use App\Enums\OrderStatus;
use App\Livewire\LicenceRequestForm;
use App\Models\LicenceRequest;
use App\Models\LicenceRequestChangeRequest;
use App\Models\LicenceRequestDocument;
use App\Models\LicenceTypeDocument;
use App\Models\Price;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class LicenceRequestFormTest extends TestCase
{
    use FastRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('licence-documents');
    }

    public function test_other_users_cannot_open_the_request(): void
    {
        $licenceRequest = $this->startRequest();
        $form = LicenceTypeDocument::factory()->for($licenceRequest->licenceType)->signedForm()->create();
        $document = LicenceRequestDocument::factory()->for($licenceRequest)->create();

        $other = User::factory()->create();

        $this->actingAs($other)->get(route('licence-requests.show', $licenceRequest))->assertForbidden();
        $this->actingAs($other)->get(route('licence-requests.edit', $licenceRequest))->assertForbidden();
        $this->actingAs($other)->get(route('licence-requests.documents.show', [$licenceRequest, $document]))->assertForbidden();
        $this->actingAs($other)->get(route('licence-requests.forms.show', [$licenceRequest, $form]))->assertForbidden();
        $this->actingAs($other)->delete(route('licence-requests.destroy', $licenceRequest))->assertForbidden();

        $this->assertModelExists($licenceRequest);
    }

    public function test_each_section_saves_on_its_own_and_the_draft_reloads(): void
    {
        $user = User::factory()->create();
        $licenceRequest = $this->startRequest($user);

        $this->actingAs($user)->get(route('licence-requests.edit', $licenceRequest))->assertSuccessful();

        Livewire::actingAs($user)
            ->test(LicenceRequestForm::class, ['licenceRequest' => $licenceRequest])
            ->assertSet('openSection', 'licence')
            ->set('level', 'international')
            ->set('licenceGrade', '')
            ->set('gradeOther', 'International C Senior')
            ->call('saveLicence')
            ->assertHasNoErrors()
            ->assertSet('openSection', 'holder');

        $licenceRequest->refresh();

        $this->assertEquals(LicenceLevel::International, $licenceRequest->level);
        $this->assertEquals('International C Senior', $licenceRequest->gradeName());
        $this->assertNull($licenceRequest->holder);

        Livewire::actingAs($user)
            ->test(LicenceRequestForm::class, ['licenceRequest' => $licenceRequest])
            ->assertSet('openSection', 'holder')
            ->assertSet('gradeOther', 'International C Senior')
            ->set('holder', $this->holderInput())
            ->call('saveHolder')
            ->assertHasNoErrors()
            ->assertDontSee(LicenceRequestSection::Parents->label());

        $this->assertEquals('Giulia Bianchi', $licenceRequest->fresh()->holder->fullName());
    }

    public function test_invalid_section_is_not_saved(): void
    {
        $user = User::factory()->create();
        $licenceRequest = $this->startRequest($user);

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
        $licenceRequest = $this->startRequest($user);

        $component = Livewire::actingAs($user)
            ->test(LicenceRequestForm::class, ['licenceRequest' => $licenceRequest])
            ->set('holder', $this->holderInput(now()->subYears(12)->toDateString()))
            ->call('saveHolder')
            ->assertHasNoErrors()
            ->assertSet('openSection', 'parents')
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
        $licenceRequest = $this->startRequest($user);
        $document = LicenceTypeDocument::factory()->for($licenceRequest->licenceType)->create(['name' => 'Medical certificate']);

        Livewire::actingAs($user)
            ->test(LicenceRequestForm::class, ['licenceRequest' => $licenceRequest])
            ->call('open', 'documents')
            ->assertSee('Medical certificate')
            ->set("files.{$document->getKey()}", UploadedFile::fake()->create('certificate.pdf', 50, 'application/pdf'))
            ->call('upload', (string) $document->getKey())
            ->assertHasNoErrors()
            ->assertSee('certificate.pdf');

        $upload = $licenceRequest->documents()->sole();

        $this->assertEquals('Medical certificate', $upload->name);
        $this->assertEquals(LicenceDocumentKind::Upload, $upload->kind);
        Storage::disk('licence-documents')->assertExists($upload->path);

        $this->actingAs($user)->get(route('licence-requests.documents.show', [$licenceRequest, $upload]))->assertSuccessful();
    }

    public function test_ranged_order_shows_the_range_and_no_proof_upload(): void
    {
        $user = User::factory()->create();
        $licenceRequest = $this->startRequest($user, Price::factory()->range(23000, 31000)->create());

        $this->assertEquals(OrderStatus::AwaitingPrice, $licenceRequest->order->status);

        Livewire::actingAs($user)
            ->test(LicenceRequestForm::class, ['licenceRequest' => $licenceRequest])
            ->call('open', 'payment')
            ->assertSee('from €230.00 to €310.00')
            ->assertDontSee(__('Upload payment proof'));
    }

    public function test_complete_request_is_submitted_unpaid_with_the_declaration(): void
    {
        $user = User::factory()->create();
        $licenceRequest = LicenceRequest::factory()->withLicenceAndHolder()->for($user)->create();

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
        $licenceRequest = LicenceRequest::factory()->changesRequested()->for($user)->create();
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
            ->set('gradeOther', 'Changed')
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
        $this->assertEquals('C Senior', $licenceRequest->gradeName());
        $this->assertEquals('Brescia', $licenceRequest->holder->birth_place);
    }

    public function test_moving_on_does_not_resolve_a_change_request_but_uploading_does(): void
    {
        $user = User::factory()->create();
        $licenceRequest = LicenceRequest::factory()->changesRequested()->for($user)->create();
        $document = LicenceTypeDocument::factory()->for($licenceRequest->licenceType)->create(['required' => false]);
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
            ->set("files.{$document->getKey()}", UploadedFile::fake()->create('certificate.pdf', 50, 'application/pdf'))
            ->call('upload', (string) $document->getKey())
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
            ->assertSet('openSection', 'review');
    }

    public function test_removing_the_second_parent_removes_their_documents(): void
    {
        $user = User::factory()->create();
        $licenceRequest = LicenceRequest::factory()->underage()->for($user)->create();
        $document = LicenceTypeDocument::factory()->for($licenceRequest->licenceType)->create(['scope' => 'each_parent', 'underage_only' => true]);

        $component = Livewire::actingAs($user)
            ->test(LicenceRequestForm::class, ['licenceRequest' => $licenceRequest])
            ->call('addParent')
            ->set('parents.1', [...$this->holderInput(), 'first_name' => 'Anna'])
            ->call('saveParents')
            ->assertHasNoErrors()
            ->set("files.{$document->getKey()}-1", UploadedFile::fake()->create('anna.pdf', 50, 'application/pdf'))
            ->call('upload', "{$document->getKey()}-1")
            ->assertHasNoErrors();

        $upload = $licenceRequest->documents()->sole();

        $component
            ->call('removeParent', 1)
            ->call('saveParents')
            ->assertHasNoErrors();

        $this->assertModelMissing($upload);
        Storage::disk('licence-documents')->assertMissing($upload->path);
    }

    public function test_an_invalid_licence_choice_keeps_the_current_order(): void
    {
        $user = User::factory()->create();
        $licenceRequest = $this->startRequest($user);
        $other = Price::factory()->create()->product;

        Livewire::actingAs($user)
            ->test(LicenceRequestForm::class, ['licenceRequest' => $licenceRequest])
            ->set('product', $other->ulid)
            ->set('licenceGrade', '')
            ->set('gradeOther', '')
            ->call('saveLicence')
            ->assertHasErrors('gradeOther');

        $licenceRequest->refresh();

        $this->assertNotEquals($other->getKey(), $licenceRequest->product_id);
        $this->assertEquals(OrderStatus::AwaitingPayment, $licenceRequest->order->status);
    }

    public function test_pre_filled_form_downloads_as_pdf(): void
    {
        $user = User::factory()->create();
        $licenceRequest = LicenceRequest::factory()->withLicenceAndHolder()->for($user)->create();
        $form = LicenceTypeDocument::factory()->for($licenceRequest->licenceType)->signedForm(LicenceFormTemplate::Privacy)->create();

        $this->actingAs($user)
            ->get(route('licence-requests.forms.show', [$licenceRequest, $form]))
            ->assertSuccessful()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_requester_deletes_a_draft(): void
    {
        $user = User::factory()->create();
        $licenceRequest = $this->startRequest($user);

        $this->actingAs($user)
            ->get(route('my-licences.index'))
            ->assertSee(route('licence-requests.show', $licenceRequest));

        $this->actingAs($user)
            ->delete(route('licence-requests.destroy', $licenceRequest))
            ->assertRedirect(route('my-licences.index'));

        $this->assertModelMissing($licenceRequest);
        $this->assertEquals(OrderStatus::Cancelled, $licenceRequest->order->fresh()->status);
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

    private function startRequest(?User $user = null, ?Price $price = null): LicenceRequest
    {
        return app(StartLicenceRequest::class)($user ?? User::factory()->create(), ($price ?? Price::factory()->create())->product);
    }
}
