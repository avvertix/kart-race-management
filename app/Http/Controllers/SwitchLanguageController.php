<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Middleware\Locale;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SwitchLanguageController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {

        $validated = $this->validate($request, [
            'lang' => ['required', Rule::in(Locale::SUPPORTED)],
        ]);

        $lang = $validated['lang'];

        session()->put('language', $lang);

        return redirect()
            ->back();
    }
}
