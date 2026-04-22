<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Championship;
use App\Models\RegistrationForm;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class UpdateChampionshipRegistrationFormController extends Controller
{
    public function __invoke(Request $request, Championship $championship)
    {
        $this->authorize('update', $championship);

        $validated = $this->validate($request, [
            'registration_form' => ['nullable', new Enum(RegistrationForm::class)],
        ]);

        $championship->update([
            'registration_form' => blank($validated['registration_form'] ?? null) ? null : RegistrationForm::from($validated['registration_form']),
        ]);

        return to_route('championships.show', $championship)
            ->with('flash.banner', __(':championship registration form updated.', [
                'championship' => $championship->title,
            ]));
    }
}
