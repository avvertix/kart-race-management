<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\LicenceRequests\DeleteLicenceRequestDraft;
use App\Enums\LicenceDocumentKind;
use App\Models\LicenceRequest;
use App\Models\LicenceRequestDocument;
use App\Models\LicenceTypeDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LicenceRequestController extends Controller
{
    /**
     * The request detail: status timeline, open change requests and order status.
     */
    public function show(LicenceRequest $licenceRequest): View
    {
        $this->authorize('view', $licenceRequest);

        $licenceRequest->load(['product', 'licenceType', 'grade', 'order.lines.price', 'order.latestPayment', 'changeRequests', 'documents', 'licence']);

        return view('licence-request.show', [
            'licenceRequest' => $licenceRequest,
        ]);
    }

    /**
     * Delete a draft, cancelling its order.
     */
    public function destroy(LicenceRequest $licenceRequest, DeleteLicenceRequestDraft $deleteDraft): RedirectResponse
    {
        $this->authorize('delete', $licenceRequest);

        $deleteDraft($licenceRequest);

        return redirect()->route('my-licences.index')
            ->with('flash.banner', __('Licence request deleted.'));
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
     * A form to sign, pre-filled with the holder and, for forms asked for each parent, that parent.
     */
    public function form(Request $request, LicenceRequest $licenceRequest, LicenceTypeDocument $document): Response
    {
        $this->authorize('view', $licenceRequest);

        abort_unless($document->licence_type_id === $licenceRequest->licence_type_id && $document->kind === LicenceDocumentKind::SignedForm && $document->template !== null, 404);

        $parentIndex = $request->integer('parent', -1);
        $parents = $licenceRequest->parentHolders();

        return Pdf::loadView($document->template->view(), [
            'template' => $document->template,
            'document' => $document,
            'licenceRequest' => $licenceRequest,
            'holder' => $licenceRequest->holder,
            'parents' => $parents,
            'parent' => $parents[$parentIndex] ?? null,
        ])->stream(Str::slug($document->name).'.pdf');
    }
}
