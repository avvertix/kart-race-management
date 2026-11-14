<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LicenceRequestStatus;
use App\Models\Licence;
use App\Models\LicenceRequest;
use App\Models\LicenceRequestDocument;
use App\Models\Payment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\EnablesLicencesAndEnrollments;
use Tests\TestCase;

class PruneLicenceRequestDocumentsTest extends TestCase
{
    use EnablesLicencesAndEnrollments;
    use FastRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('licence-documents');
        Storage::fake('payments');

        config(['races.timezone' => 'Europe/Rome']);
    }

    public function test_documents_of_a_2027_licence_are_kept_until_the_end_of_2028(): void
    {
        $licenceRequest = $this->emitted(2027);

        $this->travelTo(CarbonImmutable::parse('2028-12-31 23:59', 'Europe/Rome'));
        $this->artisan('licence-requests:prune-documents')->assertSuccessful();

        $this->assertCount(1, $licenceRequest->documents()->get());
        $this->assertNull($licenceRequest->fresh()->documents_pruned_at);

        $this->travelTo(CarbonImmutable::parse('2029-01-01 00:00', 'Europe/Rome'));
        $this->artisan('licence-requests:prune-documents')->assertSuccessful();

        $this->assertCount(0, $licenceRequest->documents()->get());
        $this->assertCount(0, $licenceRequest->payments()->get());
        Storage::disk('licence-documents')->assertMissing("licence-requests/{$licenceRequest->ulid}");
        Storage::disk('payments')->assertMissing('proof.pdf');
        $this->assertNotNull($licenceRequest->fresh()->documents_pruned_at);
    }

    public function test_the_temporary_licence_is_kept(): void
    {
        $licenceRequest = $this->emitted(2027);

        $this->travelTo(CarbonImmutable::parse('2029-02-01', 'Europe/Rome'));
        $this->artisan('licence-requests:prune-documents')->assertSuccessful();

        Storage::disk('licence-documents')->assertExists('temporary-licences/licence.pdf');
        $this->assertNotNull($licenceRequest->licence->fresh()->temporary_licence_path);
    }

    public function test_documents_of_a_rejected_request_are_deleted_12_months_after_rejection(): void
    {
        $this->travelTo(CarbonImmutable::parse('2027-03-10 10:00'));
        $licenceRequest = $this->withDocument(LicenceRequest::factory()->submitted()->create([
            'status' => LicenceRequestStatus::Rejected,
            'rejected_at' => now(),
        ]));

        $this->travelTo(CarbonImmutable::parse('2028-03-10 09:00'));
        $this->artisan('licence-requests:prune-documents')->assertSuccessful();
        $this->assertCount(1, $licenceRequest->documents()->get());

        $this->travelTo(CarbonImmutable::parse('2028-03-10 11:00'));
        $this->artisan('licence-requests:prune-documents')->assertSuccessful();
        $this->assertCount(0, $licenceRequest->documents()->get());
    }

    public function test_documents_of_requests_not_emitted_or_rejected_are_never_deleted(): void
    {
        $this->travelTo(CarbonImmutable::parse('2020-01-01'));

        $requests = collect(LicenceRequestStatus::cases())
            ->reject(fn (LicenceRequestStatus $status) => in_array($status, [LicenceRequestStatus::Emitted, LicenceRequestStatus::Rejected], true))
            ->map(fn (LicenceRequestStatus $status) => $this->withDocument(LicenceRequest::factory()->create(['status' => $status])));

        $this->travelTo(CarbonImmutable::parse('2035-01-01'));
        $this->artisan('licence-requests:prune-documents')->assertSuccessful();

        $requests->each(fn (LicenceRequest $licenceRequest) => $this->assertCount(1, $licenceRequest->documents()->get()));
    }

    public function test_a_dry_run_lists_without_deleting(): void
    {
        $licenceRequest = $this->emitted(2027);

        $this->travelTo(CarbonImmutable::parse('2029-02-01', 'Europe/Rome'));
        $this->artisan('licence-requests:prune-documents', ['--dry-run' => true])
            ->expectsOutputToContain($licenceRequest->ulid)
            ->assertSuccessful();

        $this->assertCount(1, $licenceRequest->documents()->get());
        $this->assertNull($licenceRequest->fresh()->documents_pruned_at);
    }

    public function test_the_pages_say_the_documents_were_deleted(): void
    {
        $licenceRequest = $this->emitted(2027);

        $this->travelTo(CarbonImmutable::parse('2029-01-05 08:00', 'Europe/Rome'));
        $this->artisan('licence-requests:prune-documents')->assertSuccessful();

        $this->actingAs($licenceRequest->user)
            ->get(route('licence-requests.show', $licenceRequest))
            ->assertSuccessful()
            ->assertSee('Documents deleted on')
            ->assertSee('after the retention period');

        $this->actingAs(User::factory()->organizer()->create())
            ->get(route('licence-request-reviews.show', $licenceRequest))
            ->assertSuccessful()
            ->assertSee('Documents deleted on');
    }

    /**
     * An emitted request with a document, a payment proof and a temporary licence.
     */
    private function emitted(int $validityYear): LicenceRequest
    {
        $this->travelTo(CarbonImmutable::parse(($validityYear - 1).'-11-15'));

        Storage::disk('licence-documents')->put('temporary-licences/licence.pdf', 'pdf');

        $licenceRequest = LicenceRequest::factory()->verified()->create(['status' => LicenceRequestStatus::Emitted]);
        $licence = Licence::factory()->emitted()->for($licenceRequest->user)->create(['validity_year' => $validityYear]);
        $licenceRequest->update(['licence_id' => $licence->getKey()]);

        Storage::disk('payments')->put('proof.pdf', 'proof');
        Payment::factory()->for($licenceRequest, 'payable')->create(['path' => 'proof.pdf']);

        return $this->withDocument($licenceRequest);
    }

    private function withDocument(LicenceRequest $licenceRequest): LicenceRequest
    {
        Storage::disk('licence-documents')->put("licence-requests/{$licenceRequest->ulid}/identity.pdf", 'id');
        LicenceRequestDocument::factory()->for($licenceRequest)->create(['path' => "licence-requests/{$licenceRequest->ulid}/identity.pdf"]);

        return $licenceRequest;
    }
}
