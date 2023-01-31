<?php

namespace App\Http\Controllers;

use App\Models\Championship;
use App\Models\Race;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RaceImportController extends Controller
{
    
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, Championship $championship)
    {
        $this->authorize([Race::class]);

        return view('championship.race.import', [
            'championship' => $championship,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Championship $championship)
    {
        $this->authorize([Race::class]);

        $validated = $this->validate($request, [
            'races' => 'required|string',
        ]);

        $data = collect(Str::of($validated['races'])->split('/[\n\r]+/'))->map(function($line){
            
            if(empty($line)){
                return null;
            }

            $parsedLine = str_getcsv($line, ';');
            
            return [
                'start' => $parsedLine[0] ?? null,
                'end' => $parsedLine[1] ?? null,
                'title' => $parsedLine[2] ?? null,
                'track' => $parsedLine[3] ?? null,
                'description' => $parsedLine[4] ?? null,
            ];
        })->filter();

        $validator = Validator::make(
            ['races' => $data->toArray()],
            [
                'races.*.start' => 'required|date|after:today',
                'races.*.end' => 'nullable|date|after:start',
                'races.*.title' => 'required|string|max:250|unique:' . Race::class .',title',
                'races.*.description' => 'nullable|string|max:1000',
                'races.*.track' => 'required|string|max:250',
            ],
            [],
            [
                'races.*.start' => 'start',
                'races.*.end' => 'end',
                'races.*.title' => 'title',
                'races.*.description' => 'description',
                'races.*.track' => 'track',
            ]
        );
        
        if($validator->fails()){
            $errors = $validator->errors();

            $messages = collect($errors->messages())->groupBy(function($value, $key){
                return Str::between($key, '.', '.');
            })->sortKeys()->mapWithKeys(function($values, $line){

                $msg = __('Line :line contains invalid data', [
                    'line' => $line,
                ]);

                return [$msg => $values->flatten()];
            });

            throw ValidationException::withMessages([
                'races' => $messages,
            ]);
        }

        $toCreate = collect($validator->validated()['races'])->map(function($d) use($request){

            $start_date = Str::contains($d['start'], ':') ? Carbon::parse($d['start']) : Carbon::parse("{$d['start']} 09:00");

            return [
                'event_start_at' => $start_date,
                'event_end_at' => Str::contains($d['end'], ':') ? Carbon::parse($d['end']) : Carbon::parse("{$d['end']} 18:00"),
                'title' => $d['title'],
                'description' => $d['description'],
                'track' => $d['track'],
                'registration_opens_at' => $start_date->copy()->subHours(config('races.registration.opens')),
                'registration_closes_at' => $start_date->copy()->subHours(config('races.registration.closes')),
            ];
        });

        $championship->races()->createMany($toCreate->toArray());

        return to_route('championships.races.index', $championship)
            ->with('flash.banner', __('Races imported.'));

    }

}
