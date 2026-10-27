<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DriverLicence;
use App\Models\FederationAuthorization;
use App\Models\Participant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ParticipantFederationAuthorizationController extends Controller
{
    /**
     * Store the authorization from the national sporting federation of a foreign licence holder.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validate($request, [
            'authorization' => [
                'required',
                File::types(['pdf', 'jpg', 'png'])
                    ->min(1)
                    ->max(10 * 1024), // 10 MB maximum
            ],
            'participant' => [
                'required',
                'exists:participants,uuid',
            ],
            'p' => [
                'required',
                'string',
            ],
        ]);

        $participant = Participant::whereUuid($validated['participant'])->firstOrFail();

        if ($participant->signatureContent() !== $validated['p']) {
            throw ValidationException::withMessages([
                'authorization' => __('Could not verify the upload.'),
            ]);
        }

        if ($participant->licence_type !== DriverLicence::FOREIGN) {
            throw ValidationException::withMessages([
                'authorization' => __('The authorization is required only for foreign licence holders.'),
            ]);
        }

        $path = $request->file('authorization')->store('', 'federation-authorizations');

        $participant->federationAuthorizations()->create([
            'path' => $path,
            'hash' => hash_file('sha512', Storage::disk('federation-authorizations')->path($path)),
        ]);

        return redirect($participant->qrCodeUrl())->with('status', 'federation-authorization-uploaded');
    }

    /**
     * Show the uploaded authorization.
     */
    public function show(FederationAuthorization $authorization): BinaryFileResponse
    {
        return response()
            ->file(Storage::disk('federation-authorizations')->path($authorization->path));
    }
}
