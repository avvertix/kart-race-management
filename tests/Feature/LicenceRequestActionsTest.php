<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\LicenceRequests\DeleteLicenceRequestDraft;
use App\Actions\LicenceRequests\ResubmitLicenceRequest;
use App\Actions\LicenceRequests\StartLicenceRequest;
use App\Actions\LicenceRequests\StoreLicenceRequestUpload;
use App\Actions\LicenceRequests\SubmitLicenceRequest;
use App\Actions\LicenceRequests\SwitchLicenceRequestProduct;
use App\Data\RequiredDocument;
use App\Enums\LicenceDocumentKind;
use App\Enums\LicenceDocumentScope;
use App\Enums\LicenceRequestDocumentStatus;
use App\Enums\LicenceRequestSection;
use App\Enums\LicenceRequestStatus;
use App\Enums\OrderStatus;
use App\Models\LicenceRequest;
use App\Models\LicenceRequestChangeRequest;
use App\Models\LicenceRequestDocument;
use App\Models\LicenceType;
use App\Models\LicenceTypeDocument;
use App\Models\Price;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class LicenceRequestActionsTest extends TestCase
{
    use FastRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('licence-documents');
    }

    public function test_starting_a_request_places_the_order_and_creates_a_draft(): void
    {
        $user = User::factory()->create();
        $product = Price::factory()->create(['amount' => 18000])->product;

        $licenceRequest = app(StartLicenceRequest::class)($user, $product);

        $this->assertEquals(LicenceRequestStatus::Draft, $licenceRequest->status);
        $this->assertTrue($licenceRequest->user->is($user));
        $this->assertTrue($licenceRequest->product->is($product));
        $this->assertEquals($product->licence_type_id, $licenceRequest->licence_type_id);
        $this->assertEquals(OrderStatus::AwaitingPayment, $licenceRequest->order->status);
        $this->assertTrue($licenceRequest->order->user->is($user));
    }

    public function test_only_licence_products_start_a_request(): void
    {
        $product = Price::factory()->for(Product::factory()->enrollment())->create()->product;

        $this->expectException(ValidationException::class);

        try {
            app(StartLicenceRequest::class)(User::factory()->create(), $product);
        } finally {
            $this->assertDatabaseEmpty('licence_requests');
            $this->assertDatabaseEmpty('orders');
        }
    }

    public function test_switching_product_cancels_the_order_and_places_a_new_one(): void
    {
        $user = User::factory()->create();
        $licenceType = LicenceType::factory()->create();
        $plain = Price::factory()->for(Product::factory()->for($licenceType))->create(['amount' => 18000])->product;
        $package = Price::factory()->for(Product::factory()->for($licenceType)->state(['kind' => 'package']))->create(['amount' => 25000])->product;

        $licenceRequest = app(StartLicenceRequest::class)($user, $plain);
        $firstOrder = $licenceRequest->order;

        app(SwitchLicenceRequestProduct::class)($licenceRequest, $package);

        $licenceRequest->refresh();

        $this->assertTrue($licenceRequest->product->is($package));
        $this->assertEquals(OrderStatus::Cancelled, $firstOrder->fresh()->status);
        $this->assertFalse($licenceRequest->order->is($firstOrder));
        $this->assertEquals(25000, $licenceRequest->order->total);
    }

    public function test_switching_to_another_licence_type_clears_the_licence_choice_and_its_files(): void
    {
        $user = User::factory()->create();
        $first = Price::factory()->create()->product;
        $other = Price::factory()->create()->product;

        $document = LicenceTypeDocument::factory()->for($first->licenceType)->create();

        $licenceRequest = app(StartLicenceRequest::class)($user, $first);
        $licenceRequest->update(['level' => 'national', 'grade_other' => 'C Senior']);

        $upload = app(StoreLicenceRequestUpload::class)($licenceRequest, new RequiredDocument($document), UploadedFile::fake()->create('id.pdf', 10, 'application/pdf'));

        app(SwitchLicenceRequestProduct::class)($licenceRequest, $other);

        $licenceRequest->refresh();

        $this->assertEquals($other->licence_type_id, $licenceRequest->licence_type_id);
        $this->assertNull($licenceRequest->level);
        $this->assertNull($licenceRequest->grade_other);
        $this->assertModelMissing($upload);
        Storage::disk('licence-documents')->assertMissing($upload->path);
    }

    public function test_adult_holders_need_no_parents_nor_underage_documents(): void
    {
        $licenceRequest = LicenceRequest::factory()->withLicenceAndHolder()->create();
        $licenceType = $licenceRequest->licenceType;

        $identity = LicenceTypeDocument::factory()->for($licenceType)->create(['scope' => LicenceDocumentScope::Holder]);
        LicenceTypeDocument::factory()->for($licenceType)->create(['scope' => LicenceDocumentScope::EachParent, 'underage_only' => true]);

        $licenceRequest->refresh();

        $this->assertFalse($licenceRequest->isUnderage());
        $this->assertNotContains(LicenceRequestSection::Parents, $licenceRequest->sections());
        $this->assertEquals([$identity->getKey()], $licenceRequest->requiredDocuments(LicenceDocumentKind::Upload)->map->document->map->getKey()->all());
    }

    public function test_underage_request_needs_documents_for_each_parent_entered(): void
    {
        $licenceRequest = LicenceRequest::factory()->underage()->create();

        $parentIdentity = LicenceTypeDocument::factory()->for($licenceRequest->licenceType)->create(['scope' => LicenceDocumentScope::EachParent, 'underage_only' => true]);

        $licenceRequest->refresh();

        $this->assertTrue($licenceRequest->isUnderage());
        $this->assertContains(LicenceRequestSection::Parents, $licenceRequest->sections());
        $this->assertEquals(['documents'], array_map(fn ($section) => $section->value, $licenceRequest->missingSections()));

        app(StoreLicenceRequestUpload::class)($licenceRequest, new RequiredDocument($parentIdentity, 0), UploadedFile::fake()->create('parent.pdf', 10, 'application/pdf'));

        $this->assertEquals([], $licenceRequest->refresh()->missingSections());
    }

    public function test_underage_request_cannot_be_submitted_without_a_parent(): void
    {
        $licenceRequest = LicenceRequest::factory()->underage()->create(['parents' => []]);

        $this->expectException(ValidationException::class);

        try {
            app(SubmitLicenceRequest::class)($licenceRequest);
        } finally {
            $this->assertEquals(LicenceRequestStatus::Draft, $licenceRequest->fresh()->status);
        }
    }

    public function test_complete_request_is_submitted_unpaid(): void
    {
        $licenceRequest = LicenceRequest::factory()->withLicenceAndHolder()->create();

        app(SubmitLicenceRequest::class)($licenceRequest);

        $licenceRequest->refresh();

        $this->assertEquals(LicenceRequestStatus::Submitted, $licenceRequest->status);
        $this->assertNotNull($licenceRequest->submitted_at);
        $this->assertEquals(OrderStatus::AwaitingPayment, $licenceRequest->order->status);
    }

    public function test_resubmission_waits_for_every_flagged_part(): void
    {
        $licenceRequest = LicenceRequest::factory()->changesRequested()->create();
        $changeRequest = LicenceRequestChangeRequest::factory()->for($licenceRequest)->create();
        $flagged = LicenceRequestDocument::factory()->for($licenceRequest)->flagged()->create();

        try {
            app(ResubmitLicenceRequest::class)($licenceRequest);
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
        $typeDocument = LicenceTypeDocument::factory()->for($licenceRequest->licenceType)->create();
        $flagged = LicenceRequestDocument::factory()->for($licenceRequest)->flagged()->create(['licence_type_document_id' => $typeDocument->getKey()]);

        app(StoreLicenceRequestUpload::class)($licenceRequest, new RequiredDocument($typeDocument), UploadedFile::fake()->create('new.pdf', 10, 'application/pdf'));

        $flagged->refresh();

        $this->assertEquals(LicenceRequestDocumentStatus::Pending, $flagged->status);
        $this->assertEquals('new.pdf', $flagged->original_name);
        $this->assertTrue($licenceRequest->fresh()->hasResolvedAllChanges());
    }

    public function test_accepted_documents_cannot_be_replaced_after_a_change_request(): void
    {
        $licenceRequest = LicenceRequest::factory()->changesRequested()->create();
        $typeDocument = LicenceTypeDocument::factory()->for($licenceRequest->licenceType)->create();
        LicenceRequestDocument::factory()->for($licenceRequest)->create(['licence_type_document_id' => $typeDocument->getKey(), 'status' => LicenceRequestDocumentStatus::Accepted]);

        $this->expectException(ValidationException::class);

        app(StoreLicenceRequestUpload::class)($licenceRequest, new RequiredDocument($typeDocument), UploadedFile::fake()->create('new.pdf', 10, 'application/pdf'));
    }

    public function test_deleting_a_draft_cancels_its_order_and_removes_its_files(): void
    {
        $licenceRequest = app(StartLicenceRequest::class)(User::factory()->create(), Price::factory()->create()->product);
        $document = LicenceTypeDocument::factory()->for($licenceRequest->licenceType)->create();
        $upload = app(StoreLicenceRequestUpload::class)($licenceRequest, new RequiredDocument($document), UploadedFile::fake()->create('id.pdf', 10, 'application/pdf'));

        app(DeleteLicenceRequestDraft::class)($licenceRequest);

        $this->assertModelMissing($licenceRequest);
        $this->assertModelMissing($upload);
        $this->assertEquals(OrderStatus::Cancelled, $licenceRequest->order->fresh()->status);
        Storage::disk('licence-documents')->assertMissing($upload->path);
    }
}
