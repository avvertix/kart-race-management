<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\LicenceRequests\EmitLicence;
use App\Actions\LicenceRequests\FlagLicenceRequestSection;
use App\Actions\LicenceRequests\ForwardLicenceRequest;
use App\Actions\LicenceRequests\RejectLicenceRequest;
use App\Actions\LicenceRequests\RemoveLicenceRequestFlag;
use App\Actions\LicenceRequests\RequestLicenceRequestChanges;
use App\Actions\LicenceRequests\ReviewLicenceRequestDocument;
use App\Actions\LicenceRequests\ReviewLicenceRequestPayment;
use App\Actions\LicenceRequests\SetLicenceRequestAmount;
use App\Actions\LicenceRequests\VerifyLicenceRequest;
use App\Data\RequiredDocument;
use App\Enums\LicenceProvider;
use App\Enums\LicenceRequestDocumentStatus;
use App\Enums\LicenceRequestPaymentStatus;
use App\Enums\LicenceRequestSection;
use App\Models\LicenceRequest;
use App\Models\LicenceRequestDocument;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * The organizer's review of a Licence Request, like a pull request review: mark each document as viewed, comment on
 * documents and sections, then finish the review by requesting changes or verifying the request with the amount to pay.
 * Once verified and paid, the organizer emits the licence.
 */
#[Layout('layouts.app')]
class LicenceRequestReview extends Component
{
    use WithFileUploads;

    public LicenceRequest $licenceRequest;

    #[Url(as: 'document')]
    public ?int $selectedDocumentId = null;

    /**
     * What the comment being written is about: "document:<id>" or "section:<section>".
     */
    public ?string $commentingOn = null;

    public string $comment = '';

    /**
     * The amount to pay, in euro.
     */
    public string $amount = '';

    public string $rejectionReason = '';

    public string $number = '';

    public string $validityYear = '';

    public ?UploadedFile $temporaryLicence = null;

    public function mount(LicenceRequest $licenceRequest): void
    {
        $this->authorize('review', $licenceRequest);

        $this->licenceRequest = $licenceRequest;
        $this->amount = $licenceRequest->amount === null ? '' : number_format($licenceRequest->amount / 100, 2, '.', '');
        $this->number = $licenceRequest->provider === LicenceProvider::Aci ? ($licenceRequest->previous_licence_number ?? '') : '';
        $this->validityYear = (string) LicenceRequest::validityYearFor($licenceRequest->submitted_at ?? now());
    }

    public function selectDocument(int $documentId): void
    {
        $this->authorize('review', $this->licenceRequest);

        $this->selectedDocumentId = $this->findDocument($documentId)->getKey();
    }

    /**
     * Mark the document as viewed, or back to review.
     */
    public function toggleViewed(int $documentId, ReviewLicenceRequestDocument $reviewDocument): void
    {
        $this->authorize('review', $this->licenceRequest);

        $document = $this->findDocument($documentId);

        $reviewDocument(
            $document,
            $this->user(),
            $document->status === LicenceRequestDocumentStatus::Accepted ? LicenceRequestDocumentStatus::Pending : LicenceRequestDocumentStatus::Accepted,
        );
    }

    public function startComment(string $target): void
    {
        $this->authorize('review', $this->licenceRequest);

        $this->commentingOn = $target;
        $this->comment = '';
        $this->resetErrorBag();
    }

    public function cancelComment(): void
    {
        $this->commentingOn = null;
        $this->comment = '';
    }

    /**
     * Save the comment on a document or section. It stays pending until the organizer requests changes.
     */
    public function saveComment(ReviewLicenceRequestDocument $reviewDocument, FlagLicenceRequestSection $flagSection): void
    {
        $this->authorize('review', $this->licenceRequest);

        [$kind, $key] = explode(':', (string) $this->commentingOn, 2) + [1 => ''];

        match ($kind) {
            'document' => $reviewDocument($this->findDocument((int) $key), $this->user(), LicenceRequestDocumentStatus::Flagged, $this->comment),
            'section' => $flagSection($this->licenceRequest, $this->user(), LicenceRequestSection::from($key), $this->comment),
            default => abort(404),
        };

        $this->cancelComment();
    }

    public function removeDocumentComment(int $documentId, ReviewLicenceRequestDocument $reviewDocument): void
    {
        $this->authorize('review', $this->licenceRequest);

        $reviewDocument($this->findDocument($documentId), $this->user(), LicenceRequestDocumentStatus::Pending);
    }

    public function removeSectionComment(int $changeRequestId, RemoveLicenceRequestFlag $removeFlag): void
    {
        $this->authorize('review', $this->licenceRequest);

        $removeFlag($this->licenceRequest->changeRequests()->findOrFail($changeRequestId), $this->user());
    }

    /**
     * Finish the review sending the comments to the requester.
     */
    public function requestChanges(RequestLicenceRequestChanges $requestChanges): void
    {
        $this->authorize('review', $this->licenceRequest);

        $requestChanges($this->licenceRequest, $this->user());

        Flux::modal('finish-review')->close();
    }

    /**
     * Finish the review without changes to ask, setting the amount to pay.
     */
    public function verify(VerifyLicenceRequest $verifyRequest): void
    {
        $this->authorize('review', $this->licenceRequest);

        $amount = $this->licenceRequest->payment_status === LicenceRequestPaymentStatus::Covered ? null : $this->validatedAmountInCents();

        $verifyRequest($this->licenceRequest, $this->user(), $amount);

        Flux::modal('finish-review')->close();
    }

    public function changeAmount(SetLicenceRequestAmount $setAmount): void
    {
        $this->authorize('review', $this->licenceRequest);

        $setAmount($this->licenceRequest, $this->user(), $this->validatedAmountInCents());

        Flux::modal('change-amount')->close();
    }

    public function confirmPayment(ReviewLicenceRequestPayment $reviewPayment): void
    {
        $this->authorize('review', $this->licenceRequest);

        $reviewPayment($this->licenceRequest, $this->user(), confirm: true);
    }

    public function rejectPayment(ReviewLicenceRequestPayment $reviewPayment): void
    {
        $this->authorize('review', $this->licenceRequest);

        $reviewPayment($this->licenceRequest, $this->user(), confirm: false);
    }

    public function handToProvider(ForwardLicenceRequest $forward): void
    {
        $this->authorize('review', $this->licenceRequest);

        $forward($this->licenceRequest, $this->user());
    }

    public function emit(EmitLicence $emitLicence): void
    {
        $this->authorize('review', $this->licenceRequest);

        $this->validate([
            'temporaryLicence' => ['required', 'file'],
            'number' => ['required', 'string', 'max:250'],
            'validityYear' => ['required', 'integer'],
        ], attributes: [
            'temporaryLicence' => __('Temporary licence (PDF)'),
            'number' => __('Licence number'),
            'validityYear' => __('Valid for the year'),
        ]);

        /** @var UploadedFile $temporaryLicence */
        $temporaryLicence = $this->temporaryLicence;

        $licence = $emitLicence($this->licenceRequest, $this->user(), $this->number, (int) $this->validityYear, $temporaryLicence);

        session()->flash('flash.banner', __('Licence emitted.'));

        $this->redirectRoute('licences.show', $licence);
    }

    public function reject(RejectLicenceRequest $reject): void
    {
        $this->authorize('review', $this->licenceRequest);

        $reject($this->licenceRequest, $this->user(), $this->rejectionReason);

        Flux::modal('reject-request')->close();
    }

    public function render(): View
    {
        $licenceRequest = $this->licenceRequest->refresh()->load(['user', 'documents', 'changeRequests', 'latestPayment', 'coveringOrder', 'licence']);

        $changesRequestedAt = $licenceRequest->changes_requested_at;
        $isNew = fn (RequiredDocument $required) => $licenceRequest->uploadFor($required)?->isUploadedAfter($changesRequestedAt) === true;

        $requiredDocuments = $licenceRequest->allRequiredDocuments()
            ->sortBy(fn (RequiredDocument $required) => $isNew($required) ? 0 : 1)
            ->values();

        $uploads = $requiredDocuments->map(fn (RequiredDocument $required) => $licenceRequest->uploadFor($required))->filter();

        return view('livewire.licence-request-review', [
            'requiredDocuments' => $requiredDocuments,
            'newUploadCount' => $requiredDocuments->filter($isNew)->count(),
            'selectedDocument' => $uploads->firstWhere('id', $this->selectedDocumentId) ?? $uploads->first(),
            'previousLicenceOfAnotherUser' => $licenceRequest->previousLicenceOfAnotherUser(),
        ]);
    }

    private function findDocument(int $documentId): LicenceRequestDocument
    {
        return $this->licenceRequest->documents()->findOrFail($documentId);
    }

    /**
     * Validate the amount entered, in euro, accepting a decimal comma, and convert it to cents.
     */
    private function validatedAmountInCents(): int
    {
        $this->amount = str_replace(',', '.', mb_trim($this->amount));

        $this->validate(['amount' => 'required|numeric|decimal:0,2|min:0'], attributes: ['amount' => __('Amount (€)')]);

        return (int) round(((float) $this->amount) * 100);
    }

    private function user(): User
    {
        /** @var User */
        return auth()->user();
    }
}
