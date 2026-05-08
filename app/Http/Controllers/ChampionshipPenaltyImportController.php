<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Championship;
use App\Models\ChampionshipPenalty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ChampionshipPenaltyImportController extends Controller
{
    public function create(Championship $championship)
    {
        $this->authorize('create', ChampionshipPenalty::class);

        return view('championship.penalty.import', [
            'championship' => $championship,
        ]);
    }

    public function store(Request $request, Championship $championship)
    {
        $this->authorize('create', ChampionshipPenalty::class);

        $validated = $this->validate($request, [
            'penalties' => 'required|string',
        ]);

        $data = collect(Str::of($validated['penalties'])->split('/[\n\r]+/'))->map(function ($line) {
            if (empty(mb_trim($line))) {
                return null;
            }

            $parsedLine = str_getcsv($line, ';');

            return [
                'title' => $parsedLine[0] ?? null,
                'description' => ! empty($parsedLine[1]) ? $parsedLine[1] : null,
            ];
        })->filter()->values();

        $validator = Validator::make(
            ['penalties' => $data->toArray()],
            [
                'penalties.*.title' => ['required', 'string', 'max:250'],
                'penalties.*.description' => ['nullable', 'string', 'max:2000'],
            ],
            [],
            [
                'penalties.*.title' => 'title',
                'penalties.*.description' => 'description',
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
                'penalties' => $messages,
            ]);
        }

        $championship->penalties()->createMany($validator->validated()['penalties']);

        return redirect()->route('championships.penalties.index', $championship)
            ->with('flash.banner', __(':count penalty templates imported.', ['count' => $data->count()]));
    }
}
