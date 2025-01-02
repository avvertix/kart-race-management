<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Championship;
use App\Models\OrbitsBackup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class OrbitsBackupController extends Controller
{
    protected const DISK_NAME = 'orbits-backups';

    public function __construct()
    {
        $this->authorizeResource(OrbitsBackup::class, 'orbits_backup');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $backups = OrbitsBackup::query()->with('championship')->orderBy('created_at', 'DESC')->get();

        $championships = Championship::query()->orderByDesc('start_at')->get();

        return view('orbits-backup.index', [
            'backups' => $backups,
            'championships' => $championships,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $this->validate($request, [
            'file' => [
                'required',
                File::defaults()
                    ->extensions(['oxb', 'zip', '7z'])
                    ->types(['application/x-7z-compressed', 'application/zip'])
                    ->max(50 * 1024), // 50 MB maximum
            ],
            'championship' => [
                'nullable',
                'integer',
                Rule::exists('championships', 'id'),
            ],
        ]);

        $path = $request->file->store('', self::DISK_NAME);

        $hash = Storage::disk(self::DISK_NAME)->checksum($path, ['checksum_algo' => 'sha256']);

        OrbitsBackup::create([
            'user_id' => auth()->user()->getKey(),
            'championship_id' => $validated['championship'] ?? null,
            'filename' => $request->file->getClientOriginalName(),
            'path' => $path,
            'hash' => $hash,
        ]);

        return redirect()->route('orbits-backups.index')
            ->with('flash.banner', __('Backup file uploaded.'));

    }

    /**
     * Display the specified resource.
     */
    public function show(OrbitsBackup $orbitsBackup)
    {
        return response()->download(Storage::disk(self::DISK_NAME)->path($orbitsBackup->path), $orbitsBackup->filename);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OrbitsBackup $orbitsBackup)
    {
        Storage::disk(self::DISK_NAME)->delete($orbitsBackup->path);

        $orbitsBackup->delete();

        return redirect()->route('orbits-backups.index')
            ->with('flash.banner', __('Backup file deleted.'));
    }
}
