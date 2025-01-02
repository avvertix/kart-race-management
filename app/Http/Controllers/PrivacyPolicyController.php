<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class PrivacyPolicyController extends Controller
{
    /**
     * Show the privacy policy for the application.
     *
     * @return \Illuminate\View\View
     */
    public function show(Request $request)
    {
        $policyFile = $this->localizedMarkdownPath('policy.md');

        return view('policy', [
            'policy' => Str::markdown(file_get_contents($policyFile)),
        ]);
    }

    /**
     * Find the path to a localized Markdown resource.
     *
     * @param  string  $name
     * @return string|null
     */
    protected function localizedMarkdownPath($name)
    {
        $localName = preg_replace('#(\.md)$#i', '.'.app()->getLocale().'$1', $name);

        return Arr::first([
            storage_path('markdown/'.$localName),
            storage_path('markdown/'.$name),
            resource_path('markdown/'.$localName),
            resource_path('markdown/'.$name),
        ], function ($path) {
            return file_exists($path);
        });
    }
}
