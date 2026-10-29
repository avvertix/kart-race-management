<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\LicenceRequests\EmitLicence;
use App\Actions\LicenceRequests\FlagLicenceRequestSection;
use App\Actions\LicenceRequests\ForwardLicenceRequest;
use App\Actions\LicenceRequests\RequestLicenceRequestChanges;
use App\Actions\LicenceRequests\ReviewLicenceRequestDocument;
use App\Actions\LicenceRequests\ReviewLicenceRequestPayment;
use App\Actions\LicenceRequests\SetLicenceRequestAmount;
use App\Actions\SubmitPaymentProof;
use App\Enums\LicenceDocument;
use App\Enums\LicenceProvider;
use App\Enums\LicenceRequestDocumentStatus;
use App\Enums\LicenceRequestPaymentStatus;
use App\Enums\LicenceRequestSection;
use App\Enums\LicenceRequestStatus;
use App\Enums\LicenceSource;
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
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
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

    public function test_drivers_cannot_reach_any_organizer_page_or_action(): void
    {
        $driver = User::factory()->create();
        $licenceRequest = LicenceRequest::factory()->submitted()->for($driver)->create();

        $this->actingAs($driver)->get(route('licence-request-reviews.index'))->assertForbidden();
        $this->actingAs($driver)->get(route('licence-request-reviews.show', $licenceRequest))->assertForbidden();

        foreach (['request-changes', 'forward', 'emit', 'reject', 'payment', 'flags.store'] as $action) {
            $this->actingAs($driver)->post(route("licence-request-reviews.{$action}", $licenceRequest))->assertForbidden();
        }

        $this->actingAs($driver)->put(route('licence-request-reviews.amount', $licenceRequest), ['amount' => '1'])->assertForbidden();

        $this->assertEquals(LicenceRequestStatus::Submitted, $licenceRequest->fresh()->status);
    }

    public function test_queue_lists_requests_to_process_oldest_first_without_drafts(): void
    {
        $organizer = User::factory()->organizer()->create();

        LicenceRequest::factory()->submitted()->create(['submitted_at' => now()->subDay(), 'holder' => LicenceFactory::holder('Newer', 'Request')]);
        LicenceRequest::factory()->submitted()->create(['submitted_at' => now()->subWeek(), 'holder' => LicenceFactory::holder('Older', 'Request')]);
        LicenceRequest::factory()->withLicenceAndHolder(LicenceFactory::holder('Draft', 'Request'))->create();
        LicenceRequest::factory()->submitted()->create(['status' => LicenceRequestStatus::Rejected, 'holder' => LicenceFactory::holder('Rejected', 'Request')]);

        $this->actingAs($organizer)
            ->get(route('licence-request-reviews.index'))
            ->assertSuccessful()
            ->assertSeeInOrder(['Older Request', 'Newer Request'])
            ->assertDontSee('Draft Request')
            ->assertDontSee('Rejected Request');

        $this->actingAs($organizer)
            ->get(route('licence-request-reviews.index', ['status' => 'draft']))
            ->assertSuccessful()
            ->assertSee('Draft Request')
            ->assertDontSee('Older Request');
    }

    public function test_organizer_reviews_the_request_and_its_documents(): void
    {
        $organizer = User::factory()->organizer()->create();
        $licenceRequest = LicenceRequest::factory()->submitted()->withAllDocuments()->create();
        $document = $licenceRequest->documents()->first();
        Storage::disk('licence-documents')->put($document->path, '%PDF-1.4');
        $other = Licence::factory()->create(['number' => $licenceRequest->previous_licence_number]);

        $this->actingAs($organizer)
            ->get(route('licence-request-reviews.show', [$licenceRequest, 'document' => $document->getKey()]))
            ->assertSuccessful()
            ->assertSee($licenceRequest->holder->fullName())
            ->assertSee('NAZ E')
            ->assertSee(route('licence-requests.documents.show', [$licenceRequest, $document]))
            ->assertSee('This number belongs to a licence managed by another user.')
            ->assertSee(route('licences.show', $other));

        $this->actingAs($organizer)->get(route('licence-requests.documents.show', [$licenceRequest, $document]))->assertSuccessful();
    }

    public function test_first_organizer_action_moves_the_request_to_review(): void
    {
        $organizer = User::factory()->organizer()->create();
        $licenceRequest = LicenceRequest::factory()->submitted()->withAllDocuments()->create();

        $document = $licenceRequest->documents()->first();

        app(ReviewLicenceRequestDocument::class)($document, $organizer, accept: true);

        $licenceRequest->refresh();

        $this->assertEquals(LicenceRequestStatus::InReview, $licenceRequest->status);
        $this->assertEquals($organizer->getKey(), $licenceRequest->reviewed_by);
        $this->assertEquals(LicenceRequestDocumentStatus::Accepted, $document->fresh()->status);
    }

    public function test_flags_reach_the_requester_once_changes_are_requested(): void
    {
        $organizer = User::factory()->organizer()->create();
        $licenceRequest = LicenceRequest::factory()->submitted()->withAllDocuments()->create();

        try {
            app(RequestLicenceRequestChanges::class)($licenceRequest, $organizer);
            $this->fail('Changes were requested with nothing flagged.');
        } catch (ValidationException) {
            $this->assertEquals(LicenceRequestStatus::Submitted, $licenceRequest->fresh()->status);
        }

        app(FlagLicenceRequestSection::class)($licenceRequest, $organizer, LicenceRequestSection::Holder, 'Birth place is wrong.');
        $medicalCertificate = $licenceRequest->documents()->where('document', LicenceDocument::MedicalCertificate)->sole();
        app(ReviewLicenceRequestDocument::class)($medicalCertificate, $organizer, accept: false, note: 'Blurred photo.');

        $this->actingAs($licenceRequest->user)
            ->get(route('licence-requests.show', $licenceRequest))
            ->assertDontSee('Birth place is wrong.');

        app(RequestLicenceRequestChanges::class)($licenceRequest, $organizer);

        $licenceRequest->refresh();

        $this->assertEquals(LicenceRequestStatus::ChangesRequested, $licenceRequest->status);
        $this->assertTrue($licenceRequest->isSectionFlagged(LicenceRequestSection::Holder));
        $this->assertTrue($licenceRequest->isSectionFlagged(LicenceRequestSection::Documents));

        $this->actingAs($licenceRequest->user)
            ->get(route('licence-requests.show', $licenceRequest))
            ->assertSee('Birth place is wrong.')
            ->assertSee('Blurred photo.');
    }

    public function test_setting_the_amount_lets_the_requester_pay_and_the_organizer_confirm(): void
    {
        $organizer = User::factory()->organizer()->create();
        $licenceRequest = LicenceRequest::factory()->submitted()->create();

        $this->actingAs($organizer)
            ->put(route('licence-request-reviews.amount', $licenceRequest), ['amount' => '85.50'])
            ->assertRedirect(route('licence-request-reviews.show', $licenceRequest))
            ->assertSessionHasNoErrors();

        $licenceRequest->refresh();

        $this->assertEquals(8550, $licenceRequest->amount);
        $this->assertEquals(LicenceRequestPaymentStatus::AwaitingPayment, $licenceRequest->payment_status);

        app(SubmitPaymentProof::class)($licenceRequest, UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'));

        try {
            app(SetLicenceRequestAmount::class)($licenceRequest->fresh(), $organizer, 9000);
            $this->fail('The amount changed after a proof was uploaded.');
        } catch (ValidationException) {
            $this->assertEquals(8550, $licenceRequest->fresh()->amount);
        }

        app(ReviewLicenceRequestPayment::class)($licenceRequest, $organizer, confirm: false);
        $this->assertEquals(LicenceRequestPaymentStatus::AwaitingPayment, $licenceRequest->fresh()->payment_status);

        app(SubmitPaymentProof::class)($licenceRequest->fresh(), UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'));

        $this->actingAs($organizer)
            ->post(route('licence-request-reviews.payment', $licenceRequest), ['decision' => 'confirm'])
            ->assertSessionHasNoErrors();

        $licenceRequest->refresh();

        $this->assertEquals(LicenceRequestPaymentStatus::Paid, $licenceRequest->payment_status);
        $this->assertEquals($organizer->getKey(), $licenceRequest->payment_confirmed_by);
    }

    public function test_forwarding_needs_a_settled_payment_and_no_flags(): void
    {
        $organizer = User::factory()->organizer()->create();
        $licenceRequest = LicenceRequest::factory()->submitted()->awaitingPayment()->create();

        $this->assertForwardRefused($licenceRequest, $organizer);

        $licenceRequest->update(['payment_status' => LicenceRequestPaymentStatus::Paid]);
        $flag = app(FlagLicenceRequestSection::class)($licenceRequest, $organizer, LicenceRequestSection::Holder, 'Check the name.');

        $this->assertForwardRefused($licenceRequest, $organizer);

        $flag->delete();

        app(ForwardLicenceRequest::class)($licenceRequest->fresh(), $organizer);

        $licenceRequest->refresh();

        $this->assertEquals(LicenceRequestStatus::Forwarded, $licenceRequest->status);
        $this->assertNotNull($licenceRequest->forwarded_at);
    }

    public function test_a_request_covered_by_a_package_can_be_forwarded(): void
    {
        $organizer = User::factory()->organizer()->create();
        $licenceRequest = LicenceRequest::factory()->submitted()->create(['payment_status' => LicenceRequestPaymentStatus::Covered]);

        app(ForwardLicenceRequest::class)($licenceRequest, $organizer);

        $this->assertEquals(LicenceRequestStatus::Forwarded, $licenceRequest->fresh()->status);
    }

    public function test_emitting_creates_the_licence_from_the_request(): void
    {
        $organizer = User::factory()->organizer()->create();
        $licenceRequest = LicenceRequest::factory()->submitted()->create([
            'status' => LicenceRequestStatus::Forwarded,
            'includes_competitor' => true,
            'previous_licence_number' => 'A123456',
        ]);

        $this->actingAs($organizer)
            ->get(route('licence-request-reviews.show', $licenceRequest))
            ->assertSee('value="A123456"', false);

        $response = $this->actingAs($organizer)
            ->post(route('licence-request-reviews.emit', $licenceRequest), [
                'number' => 'A123456',
                'validity_year' => '2027',
                'temporary_licence' => UploadedFile::fake()->create('licence.pdf', 100, 'application/pdf'),
            ]);

        $licence = Licence::sole();

        $response->assertRedirect(route('licences.show', $licence));
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
        $licenceRequest = LicenceRequest::factory()->submitted()->create([
            'status' => LicenceRequestStatus::Forwarded,
            'provider' => LicenceProvider::Asi,
            'licence_type' => 'driver',
            'previous_licence_number' => 'ASI-777',
        ]);

        $this->actingAs($organizer)
            ->get(route('licence-request-reviews.show', $licenceRequest))
            ->assertSuccessful()
            ->assertDontSee('value="ASI-777"', false);
    }

    public function test_a_number_already_registered_for_the_year_cannot_be_emitted(): void
    {
        $organizer = User::factory()->organizer()->create();
        $licenceRequest = LicenceRequest::factory()->submitted()->create(['status' => LicenceRequestStatus::Forwarded]);

        Licence::factory()->create(['number' => 'A123456', 'validity_year' => 2027]);

        $this->expectException(ValidationException::class);

        try {
            app(EmitLicence::class)($licenceRequest, $organizer, 'A123456', 2027, UploadedFile::fake()->create('licence.pdf', 100, 'application/pdf'));
        } finally {
            $this->assertEquals(LicenceRequestStatus::Forwarded, $licenceRequest->fresh()->status);
            $this->assertEquals(1, Licence::count());
        }
    }

    public function test_emit_form_suggests_the_next_year_for_requests_from_december(): void
    {
        $organizer = User::factory()->organizer()->create();

        $november = LicenceRequest::factory()->submitted()->create([
            'status' => LicenceRequestStatus::Forwarded,
            'submitted_at' => CarbonImmutable::parse('2026-11-30 22:30', 'UTC'),
        ]);
        $december = LicenceRequest::factory()->submitted()->create([
            'status' => LicenceRequestStatus::Forwarded,
            'submitted_at' => CarbonImmutable::parse('2026-11-30 23:30', 'UTC'),
        ]);

        $this->actingAs($organizer)->get(route('licence-request-reviews.show', $november))->assertSee('value="2026"', false);
        $this->actingAs($organizer)->get(route('licence-request-reviews.show', $december))->assertSee('value="2027"', false);
    }

    public function test_rejecting_needs_a_reason_shown_to_the_requester(): void
    {
        $organizer = User::factory()->organizer()->create();
        $licenceRequest = LicenceRequest::factory()->submitted()->create();

        $this->actingAs($organizer)
            ->post(route('licence-request-reviews.reject', $licenceRequest), ['rejection_reason' => ''])
            ->assertSessionHasErrors('rejection_reason');

        $this->actingAs($organizer)
            ->post(route('licence-request-reviews.reject', $licenceRequest), ['rejection_reason' => 'The medical certificate expired.'])
            ->assertSessionHasNoErrors();

        $licenceRequest->refresh();

        $this->assertEquals(LicenceRequestStatus::Rejected, $licenceRequest->status);
        $this->assertNotNull($licenceRequest->rejected_at);

        $this->actingAs($licenceRequest->user)
            ->get(route('licence-requests.show', $licenceRequest))
            ->assertSee('The medical certificate expired.');
    }

    private function assertForwardRefused(LicenceRequest $licenceRequest, User $organizer): void
    {
        try {
            app(ForwardLicenceRequest::class)($licenceRequest->fresh(), $organizer);
            $this->fail('The request was forwarded.');
        } catch (ValidationException) {
            $this->assertNotEquals(LicenceRequestStatus::Forwarded, $licenceRequest->fresh()->status);
        }
    }
}
