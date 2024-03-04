<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;

class SwitchLanguageController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
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
