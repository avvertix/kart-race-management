<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\TransferParticipationLicence;
use App\Enums\LicenceProvider;
use App\Enums\LicenceRole;
use App\Models\Licence;
use App\Models\Participant;
use App\Support\LicenceCatalogue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Users turn a race participation linked to their account into a Licence they manage, see TransferParticipationLicence.
 */
class LicenceTransferController extends Controller
{
    /**
     * The transfer form, pre-filled from the participation, to choose the Licence Type or complete the holder.
     */
    public function create(Request $request, Participant $participant): View
    {
        $participation = $this->participation($request, $participant);

        return view('my-licence.transfer', [
            'participant' => $participant,
            'participation' => $participation,
            'transfer' => [
                'number' => $participation->driver['licence_number'],
                'validity_year' => TransferParticipationLicence::validityYear($participation),
                'defaults' => TransferParticipationLicence::defaults($participation),
            ],
            'licenceTypes' => collect(LicenceCatalogue::providers())
                ->mapWithKeys(fn (LicenceProvider $provider) => [$provider->value => LicenceCatalogue::types($provider)->where('role', LicenceRole::Driver)->values()])
                ->all(),
        ]);
    }

    /**
     * Transfer the participation. Without a Licence Type in the request it starts from the participation alone, and
     * opens the form when that is not enough, e.g. several Licence Types match.
     */
    public function store(Request $request, Participant $participant, TransferParticipationLicence $transfer): RedirectResponse
    {
        $participation = $this->participation($request, $participant);

        $fromForm = $request->has('licence_type');

        try {
            $licence = $transfer($request->user(), $participation, $fromForm ? $request->all() : TransferParticipationLicence::defaults($participation));
        } catch (ValidationException $exception) {
            if ($fromForm) {
                throw $exception;
            }

            return redirect()->route('my-licences.transfers.create', $participant);
        }

        return redirect()->route('my-licences.show', $licence)
            ->with('flash.banner', __('Licence added from the race participation.'));
    }

    /**
     * The participation to transfer for the driver of the given one.
     */
    private function participation(Request $request, Participant $participant): Participant
    {
        $this->authorize('create', Licence::class);

        $participation = TransferParticipationLicence::participationFor($request->user(), $participant);

        abort_if($participation === null, 404);

        return $participation;
    }
}
