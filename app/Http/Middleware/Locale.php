<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class Locale
{
    /**
     * The languages the platform is translated in.
     */
    public const SUPPORTED = ['en', 'it'];

    /**
     * Use the language chosen, or the browser's, and remember it on the signed-in User for their notifications.
     *
     * @param  Closure(Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $language = Session::get('language', $request->cookie(config('session.cookie').'_lang', $this->getRequestLocale($request) ?? config('app.locale')));

        App::setLocale($language);

        $user = $request->user();

        if ($user instanceof User && $user->locale !== $language && in_array($language, self::SUPPORTED, true)) {
            $user->update(['locale' => $language]);
        }

        return $next($request);
    }

    /**
     * Retrieve the browser requested locale, if available
     *
     * @return string|null
     */
    private function getRequestLocale(Request $request)
    {
        $browser_language_preference = $request->header('ACCEPT_LANGUAGE', null);

        if (empty($browser_language_preference)) {
            return null;
        }

        $languages = collect(explode(',', $browser_language_preference));

        $keyed = $languages->map(function ($item) {
            $lang = mb_substr(mb_ltrim($item), 0, 2);
            if (mb_strlen($lang) < 2) {
                $lang = config('app.locale');
            }
            $factor = '1.0';

            if (Str::contains($item, ';q=')) {
                $factor = Str::after($item, ';q=');
            }

            return compact('lang', 'factor');
        })->sortByDesc('factor')->first();

        return $keyed['lang'] ?? null;
    }
}
