<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Models\Race;
use App\Models\Transponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RaceTranspondersImportController extends Controller
{
    public function create(Race $race)
    {
        $this->authorize('create', Transponder::class);

        $race->load('championship');

        return view('race.transponders-import', [
            'race' => $race,
            'championship' => $race->championship,
        ]);
    }

    public function store(Request $request, Race $race)
    {
        $this->authorize('create', Transponder::class);

        $race->load('championship');

        $validated = $this->validate($request, [
            'transponders' => 'required|string',
        ]);

        $existingCodes = Transponder::query()
            ->where('race_id', $race->getKey())
            ->pluck('code')
            ->map(fn ($code) => (string) $code)
            ->all();

        $seenCodes = [];

        $data = collect(Str::of($validated['transponders'])->split('/[\n\r]+/'))->map(function ($line) use ($race, $existingCodes, &$seenCodes) {
            if (empty(mb_trim($line))) {
                return null;
            }

            $parsedLine = str_getcsv($line, ';');

            $racerHash = mb_trim($parsedLine[0] ?? '');
            $code = mb_trim($parsedLine[1] ?? '');

            $participant = Participant::query()
                ->where('race_id', $race->getKey())
                ->where('racer_hash', $racerHash)
                ->first();

            $isDuplicateInFile = in_array($code, $seenCodes, true);
            $isDuplicateInRace = in_array($code, $existingCodes, true);

            if (! empty($code)) {
                $seenCodes[] = $code;
            }

            return [
                'racer_hash' => $racerHash,
                'code' => $code,
                'participant' => $participant,
                '_duplicate_in_file' => $isDuplicateInFile,
                '_duplicate_in_race' => $isDuplicateInRace,
            ];
        })->filter()->values();

        $dataForValidation = $data->map(fn ($row) => [
            'racer_hash' => $row['racer_hash'],
            'code' => $row['code'],
            'participant_found' => $row['participant'] !== null ? '1' : null,
            'duplicate_in_file' => $row['_duplicate_in_file'] ? '1' : '0',
            'duplicate_in_race' => $row['_duplicate_in_race'] ? '1' : '0',
        ])->toArray();

        $validator = Validator::make(
            ['transponders' => $dataForValidation],
            [
                'transponders.*.racer_hash' => ['required', 'string'],
                'transponders.*.code' => ['required', 'numeric', 'min:0'],
                'transponders.*.participant_found' => ['required'],
                'transponders.*.duplicate_in_file' => ['in:0'],
                'transponders.*.duplicate_in_race' => ['in:0'],
            ],
            [
                'transponders.*.participant_found.required' => __('Participant with racer hash :racer_hash not found in this race.'),
                'transponders.*.duplicate_in_file.in' => __('Transponder code appears more than once in the import.'),
                'transponders.*.duplicate_in_race.in' => __('Transponder code is already assigned in this race.'),
            ],
            [
                'transponders.*.racer_hash' => 'racer_hash',
                'transponders.*.code' => 'transponder',
                'transponders.*.participant_found' => 'participant',
                'transponders.*.duplicate_in_file' => 'transponder',
                'transponders.*.duplicate_in_race' => 'transponder',
            ]
        );

        if ($validator->fails()) {
            $errors = $validator->errors();

            $messages = collect($errors->messages())->groupBy(function ($value, $key) {
                return Str::between($key, '.', '.');
            })->sortKeys()->mapWithKeys(function ($values, $line) use ($data) {
                $lineNumber = (int) $line + 1;
                $racerHash = $data->get((int) $line)['racer_hash'] ?? $lineNumber;

                return [__('Line :line (:racer_hash) contains invalid data', ['line' => $lineNumber, 'racer_hash' => $racerHash]) => $values->flatten()];
            });

            throw ValidationException::withMessages([
                'transponders' => $messages,
            ]);
        }

        foreach ($data as $row) {
            $row['participant']->transponders()->create([
                'race_id' => $race->getKey(),
                'code' => $row['code'],
            ]);
        }

        return redirect()->route('races.transponders', $race)
            ->with('flash.banner', __(':count transponders imported.', ['count' => $data->count()]));
    }
}
