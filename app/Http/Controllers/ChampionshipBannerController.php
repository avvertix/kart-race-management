<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Championship;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Dimensions;
use Illuminate\Validation\Rules\File;

class ChampionshipBannerController extends Controller
{
    private const DISK_NAME = 'championship-banners';

    /**
     * Create the controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->authorizeResource(Championship::class, 'championship');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Championship $championship)
    {
        abort_if(is_null($championship->banner_path), 404);

        return response()
            ->file(Storage::disk(self::DISK_NAME)->path($championship->banner_path));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Championship $championship, Request $request)
    {
        $validated = $this->validate($request, [
            'banner' => [
                'required',
                File::image()
                    ->types(['jpg', 'png'])
                    ->dimensions((new Dimensions)->maxWidth(1200)->maxHeight(400))
                    ->max(10 * 1024), // 10 MB maximum
            ],
        ]);

        $path = $request->banner->store('', self::DISK_NAME);

        $oldPath = $championship->banner_path;

        $championship->banner_path = $path;

        $championship->save();

        if ($oldPath) {
            Storage::disk(self::DISK_NAME)->delete($oldPath);
        }

        return to_route('championships.edit', $championship)
            ->with('flash.banner', __('Banner uploaded.'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Championship $championship)
    {
        Storage::disk(self::DISK_NAME)->delete($championship->banner_path);

        $championship->banner_path = null;

        $championship->save();

        return to_route('championships.edit', $championship)
            ->with('flash.banner', __('Banner removed.'));
    }
}
