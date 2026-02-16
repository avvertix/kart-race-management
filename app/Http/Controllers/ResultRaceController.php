<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ProcessMyLapsResult;
use App\Models\Race;
use App\Models\RunResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;

class ResultRaceController extends Controller
{
    /**
     * Display a listing of the run results for a race.
     */
    public function index(Race $race)
    {
        $this->authorize('view', $race);

        $race->load('championship');

        $runResults = $race->results()
            ->withCount('participantResults')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('race-result.index', [
            'race' => $race,
            'championship' => $race->championship,
            'runResults' => $runResults,
        ]);
    }

    /**
     * Show the form for uploading result files.
     */
    public function create(Race $race)
    {
        $this->authorize('view', $race);

        $race->load('championship');

        return view('race-result.create', [
            'race' => $race,
            'championship' => $race->championship,
        ]);
    }

    /**
     * Store uploaded XML result files.
     */
    public function store(Request $request, Race $race)
    {
        $this->authorize('update', $race);

        $this->validate($request, [
            'files' => ['required', 'array'],
            'files.*' => [
                'required',
                File::defaults()->extensions(['xml']),
            ],
        ]);

        $processor = new ProcessMyLapsResult;

        foreach ($request->file('files') as $file) {
            $path = $file->store($race->uuid, 'race-results');

            $runResultData = $processor($file->getRealPath(), $file->getClientOriginalName());

            $runResult = $race->results()->create([
                'run_type' => $runResultData->session->value,
                'title' => $runResultData->title,
                'file_name' => $path,
            ]);

            foreach ($runResultData->results as $result) {
                $runResult->participantResults()->create($result->toArray());
            }
        }

        return redirect()->route('races.results.index', $race)
            ->with('flash.banner', __('Results uploaded successfully.'));
    }

    /**
     * Display the specified run result.
     */
    public function show(RunResult $result)
    {
        $result->load(['race.championship', 'participantResults']);

        $this->authorize('view', $result->race);

        return view('race-result.show', [
            'race' => $result->race,
            'championship' => $result->race->championship,
            'runResult' => $result,
            'participantResults' => $result->participantResults,
        ]);
    }

    /**
     * Toggle the publish status of a run result.
     */
    public function togglePublish(RunResult $result)
    {
        $result->load('race');

        $this->authorize('update', $result->race);

        $result->update([
            'published_at' => $result->isPublished() ? null : now(),
        ]);

        $message = $result->isPublished()
            ? __('Result published.')
            : __('Result unpublished.');

        return redirect()->back()->with('flash.banner', $message);
    }

    /**
     * Remove the specified run result.
     */
    public function destroy(RunResult $result)
    {
        $result->load('race');

        $this->authorize('update', $result->race);

        Storage::disk('race-results')->delete($result->file_name);

        $result->participantResults()->delete();
        $result->delete();

        return redirect()->route('races.results.index', $result->race)
            ->with('flash.banner', __('Result deleted.'));
    }
}
