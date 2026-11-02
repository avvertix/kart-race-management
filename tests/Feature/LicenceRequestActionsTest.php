<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\LicenceRequests\DeleteLicenceRequestDraft;
use App\Actions\LicenceRequests\ResubmitLicenceRequest;
use App\Actions\LicenceRequests\StartLicenceRenewal;
use App\Actions\LicenceRequests\StoreLicenceRequestUpload;
use App\Actions\LicenceRequests\SubmitLicenceRequest;
use App\Actions\SubmitPaymentProof;
use App\Data\RequiredDocument;
use App\Enums\LicenceDocument;
use App\Enums\LicenceDocumentKind;
use App\Enums\LicenceProvider;
use App\Enums\LicenceRequestDocumentStatus;
use App\Enums\LicenceRequestPaymentStatus;
use App\Enums\LicenceRequestSection;
use App\Enums\LicenceRequestStatus;
use App\Enums\LicenceRole;
use App\Models\Licence;
use App\Models\LicenceRequest;
use App\Models\LicenceRequestChangeRequest;
use App\Models\LicenceRequestDocument;
use App\Models\User;
use Database\Factories\LicenceFactory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\EnablesLicencesAndProducts;
use Tests\TestCase;

class LicenceRequestActionsTest extends TestCase
{
    use EnablesLicencesAndProducts;
    use FastRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('licence-documents');
        Storage::fake('payments');
    }

    public function test_renewing_a_licence_pre_fills_the_draft(): void
    {
        $user = User::factory()->create();
        $licence = Licence::factory()->for($user)->create([
            'number' => 'A123456',
            'includes_competitor' => true,
            'holder' => LicenceFactory::holder('Giulia', 'Bianchi'),
        ]);

        $licenceRequest = app(StartLicenceRenewal::class)($user, $licence);

        $this->assertEquals(LicenceRequestStatus::Draft, $licenceRequest->status);
        $this->assertEquals(LicenceRequestPaymentStatus::AwaitingPrice, $licenceRequest->payment_status);
        $this->assertTrue($licenceRequest->user->is($user));
        $this->assertEquals(LicenceProvider::Aci, $licenceRequest->provider);
        $this->assertEquals('naz-e', $licenceRequest->licence_type);
        $this->assertTrue($licenceRequest->includes_competitor);
        $this->assertEquals('A123456', $licenceRequest->previous_licence_number);
        $this->assertEquals(Licence::hashNumber('A123456'), $licenceRequest->previous_licence_number_hash);
        $this->assertEquals('Giulia Bianchi', $licenceRequest->holder->fullName());
    }

    public function test_renewing_a_licence_no_longer_offered_asks_for_the_type_again(): void
    {
        config(['licences.types.aci.naz-c' => ['name' => 'NAZ C', 'role' => LicenceRole::Driver, 'active' => false]]);

        $user = User::factory()->create();
        $licence = Licence::factory()->for($user)->create(['licence_type' => 'naz-c']);

        $licenceRequest = app(StartLicenceRenewal::class)($user, $licence);

        $this->assertNull($licenceRequest->licence_type);
        $this->assertFalse($licenceRequest->isSectionComplete(LicenceRequestSection::Licence));
    }

    public function test_licences_managed_by_someone_else_cannot_be_renewed(): void
    {
        $licence = Licence::factory()->create();

        $this->expectException(AuthorizationException::class);

        try {
            app(StartLicenceRenewal::class)(User::factory()->create(), $licence);
        } finally {
            $this->assertDatabaseEmpty('licence_requests');
        }
    }

    public function test_renewal_without_a_licence_starts_empty(): void
    {
        $licenceRequest = app(StartLicenceRenewal::class)(User::factory()->create());

        $this->assertNull($licenceRequest->licence_type);
        $this->assertNull($licenceRequest->previous_licence_number);
        $this->assertNull($licenceRequest->holder);
    }

    public function test_adult_holders_need_four_documents_and_no_parents(): void
    {
        $licenceRequest = LicenceRequest::factory()->withLicenceAndHolder()->create();

        $this->assertFalse($licenceRequest->isUnderage());
        $this->assertNotContains(LicenceRequestSection::Parents, $licenceRequest->sections());
        $this->assertEquals(['identity_document', 'medical_certificate'], $this->requiredKeys($licenceRequest, LicenceDocumentKind::Upload));
        $this->assertEquals(['privacy', 'delegation'], $this->requiredKeys($licenceRequest, LicenceDocumentKind::SignedForm));
    }

    public function test_underage_holders_also_need_parental_authorisation_and_each_parent_identity(): void
    {
        $holder = LicenceFactory::holder('Luca', 'Rossi');
        $holder->birth_date = now()->subYears(12)->toDateString();

        $licenceRequest = LicenceRequest::factory()->withLicenceAndHolder($holder)->create([
            'parents' => [LicenceFactory::holder('Mario', 'Rossi'), LicenceFactory::holder('Anna', 'Rossi')],
        ]);

        $this->assertTrue($licenceRequest->isUnderage());
        $this->assertContains(LicenceRequestSection::Parents, $licenceRequest->sections());
        $this->assertEquals(['identity_document', 'identity_document-0', 'identity_document-1', 'medical_certificate'], $this->requiredKeys($licenceRequest, LicenceDocumentKind::Upload));
        $this->assertEquals(['privacy', 'delegation', 'parental_authorisation'], $this->requiredKeys($licenceRequest, LicenceDocumentKind::SignedForm));
    }

    public function test_request_is_complete_once_every_document_is_uploaded(): void
    {
        $withoutDocuments = LicenceRequest::factory()->underage()->create();
        $withDocuments = LicenceRequest::factory()->underage()->withAllDocuments()->create();

        $this->assertEquals(['documents', 'forms'], array_map(fn ($section) => $section->value, $withoutDocuments->missingSections()));
        $this->assertEquals([], $withDocuments->missingSections());
    }

    public function test_underage_request_cannot_be_submitted_without_a_parent(): void
    {
        $licenceRequest = LicenceRequest::factory()->underage()->withAllDocuments()->create(['parents' => []]);

        $this->expectException(ValidationException::class);

        try {
            app(SubmitLicenceRequest::class)($licenceRequest);
        } finally {
            $this->assertEquals(LicenceRequestStatus::Draft, $licenceRequest->fresh()->status);
        }
    }

    public function test_complete_request_is_submitted_unpaid(): void
    {
        $licenceRequest = LicenceRequest::factory()->withLicenceAndHolder()->withAllDocuments()->create();

        app(SubmitLicenceRequest::class)($licenceRequest);

        $licenceRequest->refresh();

        $this->assertEquals(LicenceRequestStatus::Submitted, $licenceRequest->status);
        $this->assertNotNull($licenceRequest->submitted_at);
        $this->assertEquals(LicenceRequestPaymentStatus::AwaitingPrice, $licenceRequest->payment_status);
    }

    public function test_resubmission_waits_for_every_flagged_part(): void
    {
        $licenceRequest = LicenceRequest::factory()->changesRequested()->withAllDocuments()->create();
        $changeRequest = LicenceRequestChangeRequest::factory()->for($licenceRequest)->create();
        $flagged = $licenceRequest->documents()->first();
        $flagged->update(['status' => LicenceRequestDocumentStatus::Flagged, 'note' => 'Blurred']);

        try {
            app(ResubmitLicenceRequest::class)($licenceRequest->refresh());
            $this->fail('A request with open change requests was resubmitted.');
        } catch (ValidationException) {
            $this->assertEquals(LicenceRequestStatus::ChangesRequested, $licenceRequest->fresh()->status);
        }

        $changeRequest->update(['resolved_at' => now()]);
        $flagged->update(['status' => LicenceRequestDocumentStatus::Pending]);

        app(ResubmitLicenceRequest::class)($licenceRequest->fresh());

        $this->assertEquals(LicenceRequestStatus::Submitted, $licenceRequest->fresh()->status);
    }

    public function test_replacing_a_flagged_document_waits_for_a_new_review(): void
    {
        $licenceRequest = LicenceRequest::factory()->changesRequested()->create();
        $flagged = LicenceRequestDocument::factory()->for($licenceRequest)->flagged()->create(['document' => LicenceDocument::MedicalCertificate]);

        app(StoreLicenceRequestUpload::class)($licenceRequest, new RequiredDocument(LicenceDocument::MedicalCertificate), UploadedFile::fake()->create('new.pdf', 10, 'application/pdf'));

        $flagged->refresh();

        $this->assertEquals(LicenceRequestDocumentStatus::Pending, $flagged->status);
        $this->assertEquals('new.pdf', $flagged->original_name);
        $this->assertTrue($licenceRequest->fresh()->hasResolvedAllChanges());
    }

    public function test_accepted_documents_cannot_be_replaced_after_a_change_request(): void
    {
        $licenceRequest = LicenceRequest::factory()->changesRequested()->create();
        LicenceRequestDocument::factory()->for($licenceRequest)->create(['document' => LicenceDocument::MedicalCertificate, 'status' => LicenceRequestDocumentStatus::Accepted]);

        $this->expectException(ValidationException::class);

        app(StoreLicenceRequestUpload::class)($licenceRequest, new RequiredDocument(LicenceDocument::MedicalCertificate), UploadedFile::fake()->create('new.pdf', 10, 'application/pdf'));
    }

    public function test_documents_not_asked_for_are_refused(): void
    {
        $licenceRequest = LicenceRequest::factory()->withLicenceAndHolder()->create();

        $this->expectException(ValidationException::class);

        app(StoreLicenceRequestUpload::class)($licenceRequest, new RequiredDocument(LicenceDocument::ParentalAuthorisation), UploadedFile::fake()->create('form.pdf', 10, 'application/pdf'));
    }

    public function test_deleting_a_draft_removes_its_files(): void
    {
        $licenceRequest = LicenceRequest::factory()->withLicenceAndHolder()->create();
        $upload = app(StoreLicenceRequestUpload::class)($licenceRequest, new RequiredDocument(LicenceDocument::IdentityDocument), UploadedFile::fake()->create('id.pdf', 10, 'application/pdf'));

        app(DeleteLicenceRequestDraft::class)($licenceRequest);

        $this->assertModelMissing($licenceRequest);
        $this->assertModelMissing($upload);
        Storage::disk('licence-documents')->assertMissing($upload->path);
    }

    public function test_payment_proof_waits_for_the_amount(): void
    {
        $licenceRequest = LicenceRequest::factory()->submitted()->create();

        try {
            app(SubmitPaymentProof::class)($licenceRequest, UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'));
            $this->fail('A proof was accepted before the amount was set.');
        } catch (ValidationException $exception) {
            $this->assertEquals('The organizer has not set the amount yet.', $exception->validator->errors()->first('proof'));
        }

        $this->assertDatabaseEmpty('payments');
    }

    public function test_payment_proof_is_attached_to_the_request(): void
    {
        $licenceRequest = LicenceRequest::factory()->verified()->awaitingPayment()->create();

        $payment = app(SubmitPaymentProof::class)($licenceRequest, UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'));

        $this->assertTrue($payment->payable->is($licenceRequest));
        $this->assertEquals(LicenceRequestPaymentStatus::PaymentSubmitted, $licenceRequest->fresh()->payment_status);
        Storage::disk('payments')->assertExists($payment->path);
    }

    public function test_validity_year_moves_to_the_next_year_in_december(): void
    {
        $this->assertEquals(2026, LicenceRequest::validityYearFor(now()->setTimezone(config('races.timezone'))->setDate(2026, 11, 30)->setTime(23, 0)));
        $this->assertEquals(2027, LicenceRequest::validityYearFor(now()->setTimezone(config('races.timezone'))->setDate(2026, 12, 1)->setTime(0, 30)));
    }

    /**
     * @return array<int, string>
     */
    private function requiredKeys(LicenceRequest $licenceRequest, LicenceDocumentKind $kind): array
    {
        return $licenceRequest->requiredDocuments($kind)->map(fn (RequiredDocument $required) => $required->key())->all();
    }
}
