<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
            'lang' => 'required|in:en,it',
        ]);

        $lang = $validated['lang'];

        session()->put('language', $lang);

        return redirect()
            ->back();
    }
}
