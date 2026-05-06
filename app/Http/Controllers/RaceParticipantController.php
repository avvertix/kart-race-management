<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DeleteParticipant;
use App\Actions\RegisterParticipant;
use App\Actions\UpdateParticipantRegistration;
use App\Models\Category;
use App\Models\Championship;
use App\Models\CompetitorLicence;
use App\Models\DriverLicence;
use App\Models\Participant;
use App\Models\Race;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use ValueError;

class RaceParticipantController extends Controller
{
    /**
     * Create the controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->authorizeResource(Participant::class, 'participant');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Race $race)
    {
        $race->load(['championship']);

        return view('participant.index', [
            'race' => $race,
            'championship' => $race->championship,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Race $race, Request $request)
    {

        $templateParticipant = null;

        $race
            ->load([
                'championship',
                'championship.tires',
            ])
            ->loadCount('participants');

        try {
            $validated = $this->validate($request, [
                'from' => [
                    'sometimes',
                    'nullable',
                    'string',
                    Rule::exists('participants', 'uuid')->where(function ($query) use ($race) {
                        return $query->where('championship_id', $race->championship_id);
                    }),
                ],
            ]);

            $templateParticipant = ($validated['from'] ?? false) ? Participant::whereUuid($validated['from'])->first() : null;

        } catch (ValidationException $th) {

        }

        $lastAcceptedDateForBankTransfer = $race->event_start_at->copy()->subDays(config('races.organizer.bank_transfer_available_until_days', 3));

        $bankTransferAvailable = now()->lessThan($lastAcceptedDateForBankTransfer);

        return view('participant.create', [
            'race' => $race,
            'categories' => $race->championship->categories()->enabled()->get(),
            'participant' => $templateParticipant,
            'tires' => $race->championship->tires,
            'bankTransferAvailable' => $bankTransferAvailable,
            'lastAcceptedDateForBankTransfer' => $lastAcceptedDateForBankTransfer,
            ...$this->licenceOptions($race->championship),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Race $race, Request $request, RegisterParticipant $registerParticipant)
    {

        $participant = $registerParticipant($race, $request->all(), $request->user());

        return to_route('races.participants.index', $race)
            ->with('flash.banner', __(':participant added.', [
                'participant' => "{$participant->bib} {$participant->first_name} {$participant->last_name}",
            ]));
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Participant $participant)
    {
        $participant->load([
            'race',
            'championship',
            'racingCategory.tire',
            'tires',
            'transponders',
            'payments',
            'bonuses',
        ])->loadCount('tires', 'transponders');

        $activities = $this->buildActivityLog($participant);

        return view('participant.show', [
            'race' => $participant->race,
            'championship' => $participant->championship,
            'participant' => $participant,
            'activities' => $activities,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Participant $participant)
    {
        $participant->load(['race', 'championship']);

        return view('participant.edit', [
            'race' => $participant->race,
            'championship' => $participant->championship,
            'participant' => $participant,
            'categories' => $participant->championship->categories()->enabled()->get(),
            ...$this->licenceOptions($participant->championship),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Participant $participant, UpdateParticipantRegistration $updateRegistration)
    {

        $updatedParticipant = $updateRegistration($participant->race, $participant, $request->all(), $request->user());

        return to_route('races.participants.index', $updatedParticipant->race)
            ->with('flash.banner', __(':participant updated.', [
                'participant' => "{$updatedParticipant->bib} {$updatedParticipant->first_name} {$updatedParticipant->last_name}",
            ]));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Participant $participant, DeleteParticipant $deleteParticipant)
    {
        $race = $participant->race;

        $message = "{$participant->bib} {$participant->first_name} {$participant->last_name}";

        $deleteParticipant($participant);

        return to_route('races.participants.index', $race)
            ->with('flash.banner', __(':participant removed.', [
                'participant' => $message,
            ]));
    }

    protected function processAddressInput($input, $fieldPrefix)
    {
        return [
            'address' => $input[$fieldPrefix.'_address'],
            'city' => $input[$fieldPrefix.'_city'],
            'province' => $input[$fieldPrefix.'_province'],
            'postal_code' => $input[$fieldPrefix.'_postal_code'],
        ];
    }

    protected function processVehicle($input)
    {
        return [[
            'chassis_manufacturer' => $input['vehicle_chassis_manufacturer'],
            'engine_manufacturer' => $input['vehicle_engine_manufacturer'],
            'engine_model' => $input['vehicle_engine_model'],
            'oil_manufacturer' => $input['vehicle_oil_manufacturer'],
            'oil_type' => $input['vehicle_oil_type'],
            'oil_percentage' => $input['vehicle_oil_percentage'],
        ]];
    }

    private function buildActivityLog(Participant $participant): \Illuminate\Support\Collection
    {
        $rawActivities = $participant->activities()
            ->with('causer')
            ->latest()
            ->get();

        $categoryIds = $rawActivities->flatMap(function ($activity) {
            $attrs = $activity->properties->get('attributes', []);
            $old = $activity->properties->get('old', []);

            return array_filter([
                $attrs['category_id'] ?? null,
                $old['category_id'] ?? null,
            ], fn ($v) => ! is_null($v));
        })->unique()->values();

        $categoriesById = $categoryIds->isNotEmpty()
            ? Category::whereIn('id', $categoryIds)->pluck('name', 'id')
            : collect();

        return $rawActivities->map(function ($activity) use ($categoriesById) {
            $attrs = $activity->properties->get('attributes', []);
            $old = $activity->properties->get('old', []);

            // Fields that are hashed, raw IDs, or redundant — not useful to display
            $skippedFields = ['category', 'driver_licence', 'competitor_licence', 'added_by'];

            $changes = collect($attrs)
                ->reject(fn ($value, $field) => in_array($field, $skippedFields))
                ->flatMap(function ($newValue, $field) use ($old, $categoriesById) {
                    // driver->email and competitor->email are stored as nested arrays
                    // by EncryptSensibleParticipantData (attributes.driver.email / attributes.competitor.email)
                    if ($field === 'driver' && is_array($newValue) && array_key_exists('email', $newValue)) {
                        return [[
                            'field' => __('Driver email'),
                            'old' => $this->decryptActivityEmail($old['driver']['email'] ?? null),
                            'new' => $this->decryptActivityEmail($newValue['email']),
                        ]];
                    }
                    if ($field === 'competitor' && is_array($newValue) && array_key_exists('email', $newValue)) {
                        return [[
                            'field' => __('Competitor email'),
                            'old' => $this->decryptActivityEmail($old['competitor']['email'] ?? null),
                            'new' => $this->decryptActivityEmail($newValue['email']),
                        ]];
                    }

                    return [[
                        'field' => $this->participantFieldLabel($field),
                        'old' => $this->formatParticipantFieldValue($field, $old[$field] ?? null, $categoriesById),
                        'new' => $this->formatParticipantFieldValue($field, $newValue, $categoriesById),
                    ]];
                })->values();

            return [
                'event' => $activity->event,
                'date' => $activity->created_at,
                'causer' => $activity->causer?->name,
                'changes' => $changes,
            ];
        })->sortBy(fn ($activity) => $activity['event'] === 'created' ? 1 : 0)->values();
    }

    private function participantFieldLabel(string $field): string
    {
        return match ($field) {
            'bib' => __('Race number'),
            'category_id' => __('Category'),
            'first_name' => __('Name'),
            'last_name' => __('Surname'),
            'confirmed_at' => __('Confirmed at'),
            'licence_type' => __('Licence type'),
            'vehicles' => __('Vehicle'),
            'use_bonus' => __('Bonus'),
            'cost' => __('Cost'),
            default => $field,
        };
    }

    private function decryptActivityEmail(?string $value): string
    {
        if (is_null($value)) {
            return '—';
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return $value;
        }
    }

    private function formatParticipantFieldValue(string $field, mixed $value, $categoriesById = null): string
    {
        if (is_null($value)) {
            return '—';
        }

        return match ($field) {
            'use_bonus' => $value ? __('Yes') : __('No'),
            'confirmed_at' => $value ? (string) $value : '—',
            'licence_type' => $this->formatDriverLicence($value),
            'category_id' => $categoriesById?->get($value) ?? __('Unknown category (#:id)', ['id' => $value]),
            'vehicles' => $this->formatVehicles($value),
            'cost' => $this->formatCost($value),
            default => is_array($value) ? implode(', ', array_filter(array_values($value))) : (string) $value,
        };
    }

    private function formatDriverLicence(mixed $value): string
    {
        try {
            return DriverLicence::from((int) $value)->localizedName();
        } catch (ValueError) {
            return (string) $value;
        }
    }

    private function formatVehicles(mixed $value): string
    {
        if (! is_array($value)) {
            return (string) $value;
        }

        return collect($value)->map(function (array $vehicle) {
            $parts = array_filter([
                $vehicle['chassis_manufacturer'] ?? null,
                $vehicle['engine_manufacturer'] ?? null,
                $vehicle['engine_model'] ?? null,
                isset($vehicle['oil_manufacturer']) ? $vehicle['oil_manufacturer'].' '.$vehicle['oil_percentage'].'%' : null,
            ]);

            return implode(' / ', $parts);
        })->implode('; ');
    }

    private function formatCost(mixed $value): string
    {
        if (! is_array($value)) {
            return (string) $value;
        }

        $total = ($value['registration_cost'] ?? 0)
            + ($value['tire_cost'] ?? 0)
            - abs($value['discount'] ?? 0);

        return number_format(max(0, $total) / 100, 2, ',', '.').' €';
    }

    private function licenceOptions(Championship $championship): array
    {
        $acceptedDriverLicences = $championship->licences->accepted_driver_licences;
        $acceptedCompetitorLicences = $championship->licences->accepted_competitor_licences;

        return [
            'driverLicences' => empty($acceptedDriverLicences)
                ? DriverLicence::cases()
                : array_values(array_filter(DriverLicence::cases(), fn ($l) => in_array($l->value, $acceptedDriverLicences))),
            'competitorLicences' => empty($acceptedCompetitorLicences)
                ? CompetitorLicence::cases()
                : array_values(array_filter(CompetitorLicence::cases(), fn ($l) => in_array($l->value, $acceptedCompetitorLicences))),
        ];
    }
}
