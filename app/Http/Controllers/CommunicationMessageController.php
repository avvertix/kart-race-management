<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CommunicationMessage;
use Illuminate\Http\Request;
use Laravel\Jetstream\Jetstream;

class CommunicationMessageController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(CommunicationMessage::class, 'communication');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('communication.index', [
            'communications' => CommunicationMessage::query()->orderBy('starts_at', 'DESC')->orderBy('created_at', 'DESC')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $this->validate($request, [
            'message' => ['required', 'string', 'max:300'],
            'theme' => ['required', 'string', 'in:info'],
            'target_path' => ['sometimes', 'nullable', 'string'],
            'target_user_role' => ['sometimes', 'nullable', 'array', 'max:6'],
            'target_user_role.*' => ['required', 'string', 'in:'.collect(['anonim' => 'anonim', ...Jetstream::$roles])->keys()->join(',')],
            'starts_at' => ['required', 'date', 'after_or_equal:today'],
            'ends_at' => ['sometimes', 'nullable', 'date', 'after:starts_at'],
        ]);

        $communication = CommunicationMessage::create($validated);

        return redirect()
            ->route('communications.index')
            ->with('status', __('Scheduled message for :date', ['date' => $communication->starts_at->format('d/m/Y')]));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Communication  $communication
     * @return \Illuminate\Http\Response
     */
    public function edit(CommunicationMessage $communication)
    {
        return view('communication.edit', [
            'communication' => $communication,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Models\Communication  $communication
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CommunicationMessage $communication)
    {
        $validated = $this->validate($request, [
            'message' => ['required', 'string', 'max:300'],
            'theme' => ['required', 'string', 'in:info'],
            'target_path' => ['sometimes', 'nullable', 'string'],
            'target_user_role' => ['sometimes', 'nullable', 'array', 'max:6'],
            'target_user_role.*' => ['required', 'string', 'in:'.collect(['anonim' => 'anonim', ...Jetstream::$roles])->keys()->join(',')],
            'starts_at' => ['required', 'date', 'after_or_equal:today'],
            'ends_at' => ['sometimes', 'nullable', 'date', 'after:starts_at'],
        ]);

        $updated_communication = $communication->fill($validated);

        $updated_communication->save();

        return redirect()
            ->route('communications.index')
            ->with('status', __('Updated message :date', ['date' => $updated_communication->starts_at->format('d/m/Y')]));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Communication  $communication
     * @return \Illuminate\Http\Response
     */
    public function destroy(CommunicationMessage $communication)
    {
        $communication->delete();

        return redirect()
            ->route('communications.index')
            ->with('status', __('Message deleted.'));
    }
}
