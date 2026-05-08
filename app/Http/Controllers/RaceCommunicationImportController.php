<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CommunicationType;
use App\Models\Race;
use App\Models\RaceCommunication;
use App\Models\RunType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class RaceCommunicationImportController extends Controller
{
    public function create(Race $race)
    {
        $this->authorize('create', RaceCommunication::class);

        $race->load('championship');

        return view('race.communications-import', [
            'race' => $race,
            'championship' => $race->championship,
        ]);
    }

    public function store(Request $request, Race $race)
    {
        $this->authorize('create', RaceCommunication::class);

        $race->load('championship');

        $validated = $this->validate($request, [
            'communications' => 'required|string',
        ]);

        $data = collect(Str::of($validated['communications'])->split('/[\n\r]+/'))->map(function ($line) {
            if (empty(mb_trim($line))) {
                return null;
            }

            $parsedLine = str_getcsv($line, ';');

            $rawType = $parsedLine[0] ?? null;
            $rawRunType = $parsedLine[1] ?? null;
            $message = $parsedLine[2] ?? null;

            $type = $this->parseType($rawType);
            $runType = ! empty($rawRunType) ? $this->parseRunType($rawRunType) : null;

            return [
                'type' => $type?->value,
                'run_type' => $runType?->value,
                'message' => $message,
                '_raw_type' => $rawType,
                '_raw_run_type' => $rawRunType,
            ];
        })->filter()->values();

        $validator = Validator::make(
            ['communications' => $data->toArray()],
            [
                'communications.*.type' => ['required', 'string', 'in:communication,penalty'],
                'communications.*.run_type' => ['nullable', 'integer'],
                'communications.*.message' => ['required', 'string', 'max:2000'],
            ],
            [],
            [
                'communications.*.type' => 'type',
                'communications.*.run_type' => 'session',
                'communications.*.message' => 'message',
            ]
        );

        if ($validator->fails()) {
            $errors = $validator->errors();

            $messages = collect($errors->messages())->groupBy(function ($value, $key) {
                return Str::between($key, '.', '.');
            })->sortKeys()->mapWithKeys(function ($values, $line) {
                return [__('Line :line contains invalid data', ['line' => (int) $line + 1]) => $values->flatten()];
            });

            throw ValidationException::withMessages([
                'communications' => $messages,
            ]);
        }

        $toCreate = $data->map(fn ($row) => [
            'race_id' => $race->getKey(),
            'championship_id' => $race->championship_id,
            'user_id' => Auth::id(),
            'type' => $row['type'],
            'run_type' => $row['run_type'],
            'message' => $row['message'],
        ]);

        foreach ($toCreate as $row) {
            RaceCommunication::create($row);
        }

        return redirect()->route('races.communications.index', $race)
            ->with('flash.banner', __(':count messages imported.', ['count' => $toCreate->count()]));
    }

    private function parseType(?string $value): ?CommunicationType
    {
        if (blank($value)) {
            return null;
        }

        $lower = mb_strtolower(mb_trim($value));

        return match ($lower) {
            'communication', 'comunicazione', 'comunicazioni', 'info' => CommunicationType::Communication,
            'penalty', 'penalità', 'penalita', 'penale' => CommunicationType::Penalty,
            default => null,
        };
    }

    private function parseRunType(?string $value): ?RunType
    {
        if (blank($value)) {
            return null;
        }

        try {
            return RunType::fromString($value);
        } catch (InvalidArgumentException) {
            return null;
        }
    }
}
