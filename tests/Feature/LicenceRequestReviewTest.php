<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\LicenceRequests\EmitLicence;
use App\Actions\LicenceRequests\ForwardLicenceRequest;
use App\Actions\LicenceRequests\ResubmitLicenceRequest;
use App\Actions\LicenceRequests\ReviewLicenceRequestDocument;
use App\Actions\LicenceRequests\SetLicenceRequestAmount;
use App\Actions\LicenceRequests\StoreLicenceRequestUpload;
use App\Actions\SubmitPaymentProof;
use App\Data\RequiredDocument;
use App\Enums\LicenceDocument;
use App\Enums\LicenceProvider;
use App\Enums\LicenceRequestDocumentStatus;
use App\Enums\LicenceRequestPaymentStatus;
use App\Enums\LicenceRequestSection;
use App\Enums\LicenceRequestStatus;
use App\Enums\LicenceSource;
use App\Livewire\LicenceRequestReview;
use App\Models\DriverLicence;
use App\Models\Licence;
use App\Models\LicenceRequest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\LicenceFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

class LicenceRequestReviewTest extends TestCase
{
    use FastRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('licence-documents');
        Storage::fake('payments');
    }

    public function test_drivers_cannot_reach_the_organizer_pages(): void
    {
        $driver = User::factory()->create();
        $licenceRequest = LicenceRequest::factory()->submitted()->for($driver)->create();

        $this->actingAs($driver)->get(route('licence-request-reviews.index'))->assertForbidden();
        $this->actingAs($driver)->get(route('licence-request-reviews.show', $licenceRequest))->assertForbidden();

        $this->assertEquals(LicenceRequestStatus::Submitted, $licenceRequest->fresh()->status);
    }

    public function test_actions_do_not_shadow_livewire_client_methods(): void
    {
        $reserved = ['upload', 'uploadMultiple', 'removeUpload', 'cancelUpload', 'set', 'get', 'call', 'watch', 'entangle', 'refresh', 'commit', 'dispatch', 'on'];

        $actions = collect((new ReflectionClass(LicenceRequestReview::class))->getMethods(ReflectionMethod::IS_PUBLIC))
            ->filter(fn (ReflectionMethod $method) => $method->class === LicenceRequestReview::class)
            ->map(fn (ReflectionMethod $method) => $method->getName())
            ->all();

        $this->assertEmpty(array_intersect($reserved, $actions), 'wire:click on these names calls the Livewire client method instead of the action.');
    }

    public function test_queue_lists_requests_to_process_oldest_first_without_drafts(): void
    {
        $organizer = User::factory()->organizer()->create();

        LicenceRequest::factory()->submitted()->create(['submitted_at' => now()->subDay(), 'holder' => LicenceFactory::holder('Newer', 'Request')]);
        LicenceRequest::factory()->submitted()->create(['submitted_at' => now()->subWeek(), 'holder' => LicenceFactory::holder('Older', 'Request')]);
        LicenceRequest::factory()->verified()->create(['submitted_at' => now(), 'holder' => LicenceFactory::holder('Verified', 'Request')]);
        LicenceRequest::factory()->withLicenceAndHolder(LicenceFactory::holder('Draft', 'Request'))->create();
        LicenceRequest::factory()->submitted()->create(['status' => LicenceRequestStatus::Rejected, 'holder' => LicenceFactory::holder('Rejected', 'Request')]);

        $this->actingAs($organizer)
            ->get(route('licence-request-reviews.index'))
            ->assertSuccessful()
            ->assertSeeInOrder(['Older Request', 'Newer Request', 'Verified Request'])
            ->assertDontSee('Draft Request')
            ->assertDontSee('Rejected Request');

        $this->actingAs($organizer)
            ->get(route('licence-request-reviews.index', ['status' => 'draft']))
            ->assertSuccessful()
            ->assertSee('Draft Request')
            ->assertDontSee('Older Request');
    }

    public function test_organizer_sees_the_request_and_switches_documents(): void
    {
        $organizer = User::factory()->organizer()->create();
        $licenceRequest = LicenceRequest::factory()->submitted()->withAllDocuments()->create();
        $licenceRequest->holder->birth_date = '2001-03-09';
        $licenceRequest->update(['holder' => $licenceRequest->holder]);
        [$first, $second] = $licenceRequest->documents()->orderBy('id')->take(2)->get()->all();
        $other = Licence::factory()->create(['number' => $licenceRequest->previous_licence_number]);

        $this->actingAs($organizer)
            ->get(route('licence-request-reviews.show', $licenceRequest))
            ->assertSuccessful()
            ->assertSee($licenceRequest->holder->fullName())
            ->assertSee('Account: '.$licenceRequest->user->name)
            ->assertSee('NAZ E')
            ->assertSee('09/03/2001')
            ->assertSee('Finish review')
            ->assertSee('This number belongs to a licence managed by another user.')
            ->assertSee(route('licences.show', $other));

        Livewire::actingAs($organizer)
            ->test(LicenceRequestReview::class, ['licenceRequest' => $licenceRequest])
            ->assertSee(route('licence-requests.documents.show', [$licenceRequest, $first]))
            ->call('selectDocument', $second->getKey())
            ->assertSet('selectedDocumentId', $second->getKey())
            ->assertSee(route('licence-requests.documents.show', [$licenceRequest, $second]));
    }

    public function test_a_draft_shows_only_what_is_filled_in(): void
    {
        $organizer = User::factory()->organizer()->create();
        $licenceRequest = LicenceRequest::factory()->withLicenceAndHolder()->create();

        $this->actingAs($organizer)
            ->get(route('licence-request-reviews.show', $licenceRequest))
            ->assertSuccessful()
            ->assertSee('The driver is still filling it in.')
            ->assertSee($licenceRequest->holder->fullName())
            ->assertDontSee('Finish review')
            ->assertDontSee('Comment…')
            ->assertDontSee('Reject request…');
    }

    public function test_documents_show_what_changed_since_changes_were_requested(): void
    {
        $organizer = User::factory()->organizer()->create();
        $licenceRequest = LicenceRequest::factory()->submitted()->withAllDocuments()->create();
        $identity = $licenceRequest->documents()->where('document', LicenceDocument::IdentityDocument)->sole();
        $licenceRequest->documents()->update(['uploaded_at' => now()->subDays(3)]);

        $this->travel(-2)->days();
        $this->review($licenceRequest, $organizer)
            ->call('startComment', "document:{$identity->getKey()}")
            ->set('comment', 'Not visible.')
            ->call('saveComment')
            ->call('requestChanges')
            ->assertHasNoErrors();
        $this->travelBack();

        $this->assertNotNull($licenceRequest->fresh()->changes_requested_at);

        app(StoreLicenceRequestUpload::class)($licenceRequest->fresh(), new RequiredDocument(LicenceDocument::IdentityDocument), UploadedFile::fake()->create('identity.pdf', 10, 'application/pdf'));
        app(ResubmitLicenceRequest::class)($licenceRequest->fresh());

        $this->review($licenceRequest, $organizer)
            ->assertSee('1 document replaced since you asked for changes')
            ->assertSeeInOrder(['Identity document', 'New file', 'Your previous comment', 'Not visible.', 'Medical certificate', 'To review']);
    }

    public function test_marking_a_document_as_viewed_moves_the_request_to_review(): void
    {
        $organizer = User::factory()->organizer()->create();
        $licenceRequest = LicenceRequest::factory()->submitted()->withAllDocuments()->create();
        $document = $licenceRequest->documents()->first();

        $component = $this->review($licenceRequest, $organizer)->call('toggleViewed', $document->getKey());

        $licenceRequest->refresh();

        $this->assertEquals(LicenceRequestStatus::InReview, $licenceRequest->status);
        $this->assertEquals($organizer->getKey(), $licenceRequest->reviewed_by);
        $this->assertEquals(LicenceRequestDocumentStatus::Accepted, $document->fresh()->status);

        $component->call('toggleViewed', $document->getKey());

        $this->assertEquals(LicenceRequestDocumentStatus::Pending, $document->fresh()->status);
    }

    public function test_comments_reach_the_requester_once_changes_are_requested(): void
    {
        $organizer = User::factory()->organizer()->create();
        $licenceRequest = LicenceRequest::factory()->submitted()->withAllDocuments()->create();
        $medicalCertificate = $licenceRequest->documents()->where('document', LicenceDocument::MedicalCertificate)->sole();

        $component = $this->review($licenceRequest, $organizer)
            ->call('requestChanges')
            ->assertHasErrors('request');

        $this->assertEquals(LicenceRequestStatus::Submitted, $licenceRequest->fresh()->status);

        $component
            ->call('startComment', 'section:holder')
            ->set('comment', '')
            ->call('saveComment')
            ->assertHasErrors('note')
            ->set('comment', 'Birth place is wrong.')
            ->call('saveComment')
            ->assertHasNoErrors()
            ->assertSet('commentingOn', null)
            ->call('startComment', "document:{$medicalCertificate->getKey()}")
            ->set('comment', 'Blurred photo.')
            ->call('saveComment')
            ->assertSeeInOrder(['Request changes', 'Birth place is wrong.', 'Blurred photo.', 'Request changes (2 comments)']);

        $this->actingAs($licenceRequest->user)
            ->get(route('licence-requests.show', $licenceRequest))
            ->assertDontSee('Birth place is wrong.')
            ->assertDontSee('Needs changes');

        $this->review($licenceRequest, $organizer)->call('requestChanges')->assertHasNoErrors();

        $licenceRequest->refresh();

        $this->assertEquals(LicenceRequestStatus::ChangesRequested, $licenceRequest->status);
        $this->assertTrue($licenceRequest->isSectionFlagged(LicenceRequestSection::Holder));
        $this->assertTrue($licenceRequest->isSectionFlagged(LicenceRequestSection::Documents));

        $this->actingAs($licenceRequest->user)
            ->get(route('licence-requests.show', $licenceRequest))
            ->assertSee('Birth place is wrong.')
            ->assertSee('Blurred photo.')
            ->assertSeeInOrder(['Medical certificate', 'Needs changes']);
    }

    public function test_removed_comments_are_not_sent(): void
    {
        $organizer = User::factory()->organizer()->create();
        $licenceRequest = LicenceRequest::factory()->submitted()->withAllDocuments()->create();
        $document = $licenceRequest->documents()->first();

        $component = $this->review($licenceRequest, $organizer)
            ->call('startComment', 'section:licence')
            ->set('comment', 'Wrong type.')
            ->call('saveComment')
            ->call('startComment', "document:{$document->getKey()}")
            ->set('comment', 'Expired.')
            ->call('saveComment');

        $component
            ->call('removeSectionComment', $licenceRequest->changeRequests()->sole()->getKey())
            ->call('removeDocumentComment', $document->getKey())
            ->assertDontSee('Wrong type.')
            ->assertDontSee('Expired.');

        $this->assertTrue($licenceRequest->fresh()->hasResolvedAllChanges());
        $this->assertNull($document->fresh()->note);
    }

    public function test_verifying_needs_every_document_viewed_no_comments_and_the_amount(): void
    {
        $organizer = User::factory()->organizer()->create();
        $licenceRequest = LicenceRequest::factory()->submitted()->withAllDocuments()->create();
        $first = $licenceRequest->documents()->first();

        $component = $this->review($licenceRequest, $organizer)
            ->set('amount', '85.50')
            ->call('verify')
            ->assertHasErrors('request');

        $licenceRequest->documents()->update(['status' => LicenceRequestDocumentStatus::Accepted]);

        $component
            ->call('startComment', "document:{$first->getKey()}")
            ->set('comment', 'Expired.')
            ->call('saveComment')
            ->call('verify')
            ->assertHasErrors('request')
            ->call('toggleViewed', $first->getKey())
            ->set('amount', '')
            ->call('verify')
            ->assertHasErrors('amount')
            ->set('amount', '85,50')
            ->call('verify')
            ->assertHasNoErrors();

        $licenceRequest->refresh();

        $this->assertEquals(LicenceRequestStatus::Verified, $licenceRequest->status);
        $this->assertNotNull($licenceRequest->verified_at);
        $this->assertEquals(8550, $licenceRequest->amount);
        $this->assertEquals(LicenceRequestPaymentStatus::AwaitingPayment, $licenceRequest->payment_status);
        $this->assertEquals($organizer->getKey(), $licenceRequest->priced_by);

        $component->assertSee('Waiting for the driver\'s payment of €85.50');

        $this->actingAs($licenceRequest->user)
            ->get(route('licence-requests.show', $licenceRequest))
            ->assertSee('Pay €85.50');
    }

    public function test_a_request_covered_by_a_package_is_verified_without_an_amount(): void
    {
        $organizer = User::factory()->organizer()->create();
        $licenceRequest = LicenceRequest::factory()->submitted()->withAllDocuments()->create(['payment_status' => LicenceRequestPaymentStatus::Covered]);
        $licenceRequest->documents()->update(['status' => LicenceRequestDocumentStatus::Accepted]);

        $this->review($licenceRequest, $organizer)
            ->call('verify')
            ->assertHasNoErrors();

        $licenceRequest->refresh();

        $this->assertEquals(LicenceRequestStatus::Verified, $licenceRequest->status);
        $this->assertEquals(LicenceRequestPaymentStatus::Covered, $licenceRequest->payment_status);
        $this->assertNull($licenceRequest->amount);
    }

    public function test_the_organizer_confirms_or_rejects_the_payment_proof(): void
    {
        $organizer = User::factory()->organizer()->create();
        $licenceRequest = LicenceRequest::factory()->verified()->awaitingPayment(8550)->create();

        $this->review($licenceRequest, $organizer)
            ->set('amount', '90')
            ->call('changeAmount')
            ->assertHasNoErrors();

        $this->assertEquals(9000, $licenceRequest->fresh()->amount);

        app(SubmitPaymentProof::class)($licenceRequest->fresh(), UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'));

        try {
            app(SetLicenceRequestAmount::class)($licenceRequest->fresh(), $organizer, 9500);
            $this->fail('The amount changed after a proof was uploaded.');
        } catch (ValidationException) {
            $this->assertEquals(9000, $licenceRequest->fresh()->amount);
        }

        $this->review($licenceRequest, $organizer)->call('rejectPayment')->assertHasNoErrors();
        $this->assertEquals(LicenceRequestPaymentStatus::AwaitingPayment, $licenceRequest->fresh()->payment_status);

        app(SubmitPaymentProof::class)($licenceRequest->fresh(), UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'));

        $this->review($licenceRequest, $organizer)->call('confirmPayment')->assertHasNoErrors();

        $licenceRequest->refresh();

        $this->assertEquals(LicenceRequestPaymentStatus::Paid, $licenceRequest->payment_status);
        $this->assertEquals($organizer->getKey(), $licenceRequest->payment_confirmed_by);
    }

    public function test_emitting_and_handing_to_the_provider_need_a_settled_payment(): void
    {
        $organizer = User::factory()->organizer()->create();
        $licenceRequest = LicenceRequest::factory()->verified()->awaitingPayment()->create();

        $this->review($licenceRequest, $organizer)
            ->assertSee('Waiting for the driver\'s payment.')
            ->call('handToProvider')
            ->assertHasErrors('request');

        $this->expectValidationError(fn () => app(EmitLicence::class)($licenceRequest, $organizer, 'A123456', 2027, UploadedFile::fake()->create('licence.pdf', 100, 'application/pdf')));
        $this->assertEquals(0, Licence::count());

        $licenceRequest->update(['payment_status' => LicenceRequestPaymentStatus::Paid]);

        $this->review($licenceRequest, $organizer)
            ->assertSee('Handed to ACI Sport, waiting for the licence')
            ->call('handToProvider')
            ->assertHasNoErrors();

        $licenceRequest->refresh();

        $this->assertEquals(LicenceRequestStatus::Forwarded, $licenceRequest->status);
        $this->assertNotNull($licenceRequest->forwarded_at);

        $this->actingAs($licenceRequest->user)
            ->get(route('licence-requests.show', $licenceRequest))
            ->assertSee('Sent to the provider')
            ->assertDontSee('With the provider');
    }

    public function test_requests_not_verified_cannot_be_emitted_or_handed_to_the_provider(): void
    {
        $organizer = User::factory()->organizer()->create();
        $licenceRequest = LicenceRequest::factory()->submitted()->create(['payment_status' => LicenceRequestPaymentStatus::Covered]);

        $this->expectValidationError(fn () => app(ForwardLicenceRequest::class)($licenceRequest, $organizer));
        $this->expectValidationError(fn () => app(EmitLicence::class)($licenceRequest, $organizer, 'A123456', 2027, UploadedFile::fake()->create('licence.pdf', 100, 'application/pdf')));

        $this->assertNotEquals(LicenceRequestStatus::Forwarded, $licenceRequest->fresh()->status);
        $this->assertEquals(0, Licence::count());
    }

    public function test_emitting_creates_the_licence_from_the_request(): void
    {
        $organizer = User::factory()->organizer()->create();
        $licenceRequest = LicenceRequest::factory()->verified()->create([
            'payment_status' => LicenceRequestPaymentStatus::Paid,
            'includes_competitor' => true,
            'previous_licence_number' => 'A123456',
        ]);

        $component = $this->review($licenceRequest, $organizer)
            ->assertSet('number', 'A123456')
            ->set('validityYear', '2027')
            ->set('temporaryLicence', UploadedFile::fake()->create('licence.pdf', 100, 'application/pdf'))
            ->call('emit')
            ->assertHasNoErrors();

        $licence = Licence::sole();

        $component->assertRedirect(route('licences.show', $licence));
        $licenceRequest->refresh();

        $this->assertEquals(LicenceRequestStatus::Emitted, $licenceRequest->status);
        $this->assertTrue($licenceRequest->licence->is($licence));
        $this->assertTrue($licence->licenceRequest->is($licenceRequest));
        $this->assertTrue($licence->user->is($licenceRequest->user));
        $this->assertEquals(LicenceSource::Emitted, $licence->source);
        $this->assertEquals(LicenceProvider::Aci, $licence->provider);
        $this->assertEquals('naz-e', $licence->licence_type);
        $this->assertTrue($licence->includes_competitor);
        $this->assertEquals(DriverLicence::LOCAL_NATIONAL, $licence->race_licence_category);
        $this->assertEquals(2027, $licence->validity_year);
        $this->assertEquals($licenceRequest->holder->fullName(), $licence->holder->fullName());
        Storage::disk('licence-documents')->assertExists($licence->temporary_licence_path);

        $row = DB::table('licences')->where('id', $licence->getKey())->first();

        $this->assertStringNotContainsString('A123456', $row->number);
        $this->assertStringNotContainsString($licence->holder->last_name, $row->holder);
        $this->assertEquals(Licence::hashNumber('A123456'), $row->number_hash);
    }

    public function test_asi_emit_form_does_not_suggest_the_previous_number(): void
    {
        $organizer = User::factory()->organizer()->create();
        $licenceRequest = LicenceRequest::factory()->verified()->create([
            'provider' => LicenceProvider::Asi,
            'licence_type' => 'driver',
            'previous_licence_number' => 'ASI-777',
        ]);

        $this->review($licenceRequest, $organizer)->assertSet('number', '');
    }

    public function test_a_number_already_registered_for_the_year_cannot_be_emitted(): void
    {
        $organizer = User::factory()->organizer()->create();
        $licenceRequest = LicenceRequest::factory()->verified()->create(['status' => LicenceRequestStatus::Forwarded, 'payment_status' => LicenceRequestPaymentStatus::Paid]);

        Licence::factory()->create(['number' => 'A123456', 'validity_year' => 2027]);

        $this->expectValidationError(fn () => app(EmitLicence::class)($licenceRequest, $organizer, 'A123456', 2027, UploadedFile::fake()->create('licence.pdf', 100, 'application/pdf')));

        $this->assertEquals(LicenceRequestStatus::Forwarded, $licenceRequest->fresh()->status);
        $this->assertEquals(1, Licence::count());
    }

    public function test_emit_form_suggests_the_next_year_for_requests_from_december(): void
    {
        $organizer = User::factory()->organizer()->create();

        $november = LicenceRequest::factory()->verified()->create(['submitted_at' => CarbonImmutable::parse('2026-11-30 22:30', 'UTC')]);
        $december = LicenceRequest::factory()->verified()->create(['submitted_at' => CarbonImmutable::parse('2026-11-30 23:30', 'UTC')]);

        $this->review($november, $organizer)->assertSet('validityYear', '2026');
        $this->review($december, $organizer)->assertSet('validityYear', '2027');
    }

    public function test_rejecting_needs_a_reason_shown_to_the_requester(): void
    {
        $organizer = User::factory()->organizer()->create();
        $licenceRequest = LicenceRequest::factory()->submitted()->create();

        $this->review($licenceRequest, $organizer)
            ->call('reject')
            ->assertHasErrors('rejection_reason')
            ->set('rejectionReason', 'The medical certificate expired.')
            ->call('reject')
            ->assertHasNoErrors();

        $licenceRequest->refresh();

        $this->assertEquals(LicenceRequestStatus::Rejected, $licenceRequest->status);
        $this->assertNotNull($licenceRequest->rejected_at);

        $this->actingAs($licenceRequest->user)
            ->get(route('licence-requests.show', $licenceRequest))
            ->assertSee('The medical certificate expired.');
    }

    public function test_a_flagged_document_needs_a_comment(): void
    {
        $organizer = User::factory()->organizer()->create();
        $licenceRequest = LicenceRequest::factory()->submitted()->withAllDocuments()->create();
        $document = $licenceRequest->documents()->first();

        $this->expectValidationError(fn () => app(ReviewLicenceRequestDocument::class)($document, $organizer, LicenceRequestDocumentStatus::Flagged));

        $this->assertEquals(LicenceRequestDocumentStatus::Pending, $document->fresh()->status);
    }

    private function review(LicenceRequest $licenceRequest, User $organizer): Testable
    {
        return Livewire::actingAs($organizer)->test(LicenceRequestReview::class, ['licenceRequest' => $licenceRequest->fresh()]);
    }

    private function expectValidationError(callable $action): void
    {
        try {
            $action();
            $this->fail('The action did not fail validation.');
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }
    }
}
