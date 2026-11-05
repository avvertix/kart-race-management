<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\LicenceRequests\DeleteLicenceRequestDraft;
use App\Actions\LicenceRequests\StartLicenceRenewal;
use App\Actions\SubmitPaymentProof;
use App\Data\RequiredDocument;
use App\Enums\LicenceDocument;
use App\Enums\LicenceDocumentKind;
use App\Models\Licence;
use App\Models\LicenceRequest;
use App\Models\LicenceRequestDocument;
use App\Models\Order;
use App\Support\LicenceCatalogue;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LicenceRequestController extends Controller
{
    /**
     * Start a renewal: empty, or pre-filled from one of the user's licences, and covered by a paid Package when started
     * from its Order.
     */
    public function store(Request $request, StartLicenceRenewal $startRenewal): RedirectResponse
    {
        $this->authorize('create', LicenceRequest::class);

        $request->validate([
            'licence' => ['nullable', 'string'],
            'order' => ['nullable', 'string'],
        ]);

        $previous = $request->filled('licence')
            ? Licence::query()->managedBy($request->user())->where('ulid', $request->input('licence'))->firstOrFail()
            : null;

        $coveringOrder = $request->filled('order')
            ? Order::query()->where('user_id', $request->user()->getKey())->where('ulid', $request->input('order'))->firstOrFail()
            : null;

        $licenceRequest = DB::transaction(function () use ($request, $startRenewal, $previous, $coveringOrder) {
            if ($coveringOrder !== null) {
                $locked = Order::query()->whereKey($coveringOrder->getKey())->lockForUpdate()->firstOrFail();

                if (! $locked->canStartLicenceRenewal()) {
                    throw ValidationException::withMessages(['order' => __('This order doesn\'t cover a licence renewal, or it was already started.')]);
                }
            }

            return $startRenewal($request->user(), $previous, $coveringOrder);
        });

        return redirect()->route('licence-requests.edit', $licenceRequest);
    }

    /**
     * The request detail: status timeline, open change requests and payment.
     */
    public function show(LicenceRequest $licenceRequest): View
    {
        $this->authorize('view', $licenceRequest);

        $licenceRequest->load(['latestPayment', 'coveringOrder', 'changeRequests', 'documents', 'licence']);

        return view('licence-request.show', [
            'licenceRequest' => $licenceRequest,
        ]);
    }

    /**
     * Delete a draft.
     */
    public function destroy(LicenceRequest $licenceRequest, DeleteLicenceRequestDraft $deleteDraft): RedirectResponse
    {
        $this->authorize('delete', $licenceRequest);

        $deleteDraft($licenceRequest);

        return redirect()->route('my-licences.index')
            ->with('flash.banner', __('Licence request deleted.'));
    }

    /**
     * Upload the payment proof once the organizer set the amount.
     */
    public function pay(Request $request, LicenceRequest $licenceRequest, SubmitPaymentProof $submitPaymentProof): RedirectResponse
    {
        $this->authorize('pay', $licenceRequest);

        $request->validate([
            'proof' => ['required', 'file'],
        ]);

        $submitPaymentProof($licenceRequest, $request->file('proof'));

        return redirect()->route('licence-requests.show', $licenceRequest)
            ->with('flash.banner', __('Payment proof uploaded. The organizer will confirm it.'));
    }

    /**
     * Open an uploaded document or signed form.
     */
    public function document(LicenceRequest $licenceRequest, LicenceRequestDocument $document): BinaryFileResponse
    {
        $this->authorize('view', $licenceRequest);

        abort_unless($document->licence_request_id === $licenceRequest->getKey(), 404);

        return response()->file(Storage::disk('licence-documents')->path($document->path));
    }

    /**
     * A form to sign, pre-filled with the holder and the parents entered, or the one published by the provider.
     */
    public function form(LicenceRequest $licenceRequest, LicenceDocument $document): Response|RedirectResponse
    {
        $this->authorize('view', $licenceRequest);

        $isAskedFor = $licenceRequest->requiredDocuments(LicenceDocumentKind::SignedForm)
            ->contains(fn (RequiredDocument $required) => $required->document === $document);

        abort_unless($isAskedFor, 404);

        $providerFormUrl = LicenceCatalogue::formUrl($licenceRequest->provider, $document);

        if ($providerFormUrl !== null) {
            return redirect()->away($providerFormUrl);
        }

        return Pdf::loadView((string) $document->formView(), [
            'document' => $document,
            'licenceRequest' => $licenceRequest,
            'holder' => $licenceRequest->holder,
            'parents' => $licenceRequest->parentHolders(),
        ])->stream(Str::slug($document->label()).'.pdf');
    }
}
