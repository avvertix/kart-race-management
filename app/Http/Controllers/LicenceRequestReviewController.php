<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\LicenceRequests\EmitLicence;
use App\Actions\LicenceRequests\FlagLicenceRequestSection;
use App\Actions\LicenceRequests\ForwardLicenceRequest;
use App\Actions\LicenceRequests\RejectLicenceRequest;
use App\Actions\LicenceRequests\RemoveLicenceRequestFlag;
use App\Actions\LicenceRequests\RequestLicenceRequestChanges;
use App\Actions\LicenceRequests\ReviewLicenceRequestDocument;
use App\Actions\LicenceRequests\ReviewLicenceRequestPayment;
use App\Actions\LicenceRequests\SetLicenceRequestAmount;
use App\Enums\LicenceProvider;
use App\Enums\LicenceRequestSection;
use App\Enums\LicenceRequestStatus;
use App\Models\LicenceRequest;
use App\Models\LicenceRequestChangeRequest;
use App\Models\LicenceRequestDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The organizer's side of Licence Requests: the queue, the review page and the actions that move a request on.
 */
class LicenceRequestReviewController extends Controller
{
    /**
     * The requests, oldest submitted first, of one status or of those still open.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', LicenceRequest::class);

        $request->validate([
            'status' => ['nullable', Rule::enum(LicenceRequestStatus::class)],
        ]);

        $status = $request->enum('status', LicenceRequestStatus::class);

        return view('licence-request-review.index', [
            'status' => $status,
            'licenceRequests' => LicenceRequest::query()
                ->with('documents')
                ->when(
                    $status,
                    fn ($query) => $query->where('status', $status),
                    fn ($query) => $query->toProcess(),
                )
                ->orderBy('submitted_at')
                ->orderBy('created_at')
                ->paginate(50)
                ->withQueryString(),
        ]);
    }

    /**
     * The review page: sections and documents on one side, the selected document on the other.
     */
    public function show(Request $request, LicenceRequest $licenceRequest): View
    {
        $this->authorize('review', $licenceRequest);

        $licenceRequest->load(['user', 'documents', 'changeRequests', 'latestPayment', 'coveringOrder', 'licence']);

        $selectedDocument = $licenceRequest->documents->firstWhere('id', $request->integer('document'))
            ?? $licenceRequest->documents->first();

        return view('licence-request-review.show', [
            'licenceRequest' => $licenceRequest,
            'selectedDocument' => $selectedDocument,
            'previousLicenceOfAnotherUser' => $licenceRequest->previousLicenceOfAnotherUser(),
            'suggestedNumber' => $licenceRequest->provider === LicenceProvider::Aci ? $licenceRequest->previous_licence_number : null,
            'suggestedValidityYear' => LicenceRequest::validityYearFor($licenceRequest->submitted_at ?? now()),
        ]);
    }

    /**
     * Accept a document or flag it with a note.
     */
    public function reviewDocument(Request $request, LicenceRequest $licenceRequest, LicenceRequestDocument $document, ReviewLicenceRequestDocument $reviewDocument): RedirectResponse
    {
        $this->authorize('review', $licenceRequest);

        abort_unless($document->licence_request_id === $licenceRequest->getKey(), 404);

        $validated = $request->validate([
            'decision' => ['required', Rule::in(['accept', 'flag'])],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $reviewDocument($document, $request->user(), $validated['decision'] === 'accept', $validated['note'] ?? null);

        return redirect()->route('licence-request-reviews.show', [$licenceRequest, 'document' => $document->getKey()]);
    }

    /**
     * Flag a section with a note.
     */
    public function flagSection(Request $request, LicenceRequest $licenceRequest, FlagLicenceRequestSection $flagSection): RedirectResponse
    {
        $this->authorize('review', $licenceRequest);

        $validated = $request->validate([
            'section' => ['required', Rule::enum(LicenceRequestSection::class)],
            'note' => ['required', 'string', 'max:1000'],
        ]);

        $flagSection($licenceRequest, $request->user(), LicenceRequestSection::from($validated['section']), $validated['note']);

        return redirect()->route('licence-request-reviews.show', $licenceRequest);
    }

    /**
     * Remove a section flag not sent yet.
     */
    public function removeFlag(Request $request, LicenceRequest $licenceRequest, LicenceRequestChangeRequest $changeRequest, RemoveLicenceRequestFlag $removeFlag): RedirectResponse
    {
        $this->authorize('review', $licenceRequest);

        abort_unless($changeRequest->licence_request_id === $licenceRequest->getKey(), 404);

        $removeFlag($changeRequest, $request->user());

        return redirect()->route('licence-request-reviews.show', $licenceRequest);
    }

    /**
     * Send the flags to the requester.
     */
    public function requestChanges(Request $request, LicenceRequest $licenceRequest, RequestLicenceRequestChanges $requestChanges): RedirectResponse
    {
        $this->authorize('review', $licenceRequest);

        $requestChanges($licenceRequest, $request->user());

        return redirect()->route('licence-request-reviews.show', $licenceRequest)
            ->with('flash.banner', __('Changes requested.'));
    }

    /**
     * Set the amount to pay. The amount is in euro.
     */
    public function setAmount(Request $request, LicenceRequest $licenceRequest, SetLicenceRequestAmount $setAmount): RedirectResponse
    {
        $this->authorize('review', $licenceRequest);

        $validated = $request->validate([
            'amount' => 'required|numeric|decimal:0,2|min:0',
        ]);

        $setAmount($licenceRequest, $request->user(), (int) round(((float) $validated['amount']) * 100));

        return redirect()->route('licence-request-reviews.show', $licenceRequest)
            ->with('flash.banner', __('Amount set.'));
    }

    /**
     * Confirm or reject the uploaded payment proof.
     */
    public function reviewPayment(Request $request, LicenceRequest $licenceRequest, ReviewLicenceRequestPayment $reviewPayment): RedirectResponse
    {
        $this->authorize('review', $licenceRequest);

        $validated = $request->validate([
            'decision' => ['required', Rule::in(['confirm', 'reject'])],
        ]);

        $reviewPayment($licenceRequest, $request->user(), $validated['decision'] === 'confirm');

        return redirect()->route('licence-request-reviews.show', $licenceRequest)
            ->with('flash.banner', $validated['decision'] === 'confirm' ? __('Payment confirmed.') : __('Payment rejected.'));
    }

    /**
     * Mark the request as sent to the Licence Provider.
     */
    public function forward(Request $request, LicenceRequest $licenceRequest, ForwardLicenceRequest $forward): RedirectResponse
    {
        $this->authorize('review', $licenceRequest);

        $forward($licenceRequest, $request->user());

        return redirect()->route('licence-request-reviews.show', $licenceRequest)
            ->with('flash.banner', __('Request marked as forwarded.'));
    }

    /**
     * Create the Licence emitted by the provider.
     */
    public function emit(Request $request, LicenceRequest $licenceRequest, EmitLicence $emit): RedirectResponse
    {
        $this->authorize('review', $licenceRequest);

        $validated = $request->validate([
            'number' => ['required', 'string', 'max:250'],
            'validity_year' => ['required', 'integer'],
            'temporary_licence' => ['required', 'file'],
        ]);

        $licence = $emit($licenceRequest, $request->user(), $validated['number'], (int) $validated['validity_year'], $request->file('temporary_licence'));

        return redirect()->route('licences.show', $licence)
            ->with('flash.banner', __('Licence emitted.'));
    }

    /**
     * Close the request without a licence.
     */
    public function reject(Request $request, LicenceRequest $licenceRequest, RejectLicenceRequest $reject): RedirectResponse
    {
        $this->authorize('review', $licenceRequest);

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $reject($licenceRequest, $request->user(), $validated['rejection_reason']);

        return redirect()->route('licence-request-reviews.show', $licenceRequest)
            ->with('flash.banner', __('Request rejected.'));
    }
}
