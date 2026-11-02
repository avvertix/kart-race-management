<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\LicenceRequestStatus;
use App\Models\LicenceRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LicenceRequestReviewController extends Controller
{
    /**
     * The requests, oldest submitted first, of one status or of those still open. The review itself is App\Livewire\LicenceRequestReview.
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
}
