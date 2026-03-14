<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PaymentChannelType;
use App\Models\Race;
use Illuminate\Http\Request;

class RacePaymentsController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request, Race $race)
    {
        $this->authorize('update', $race);

        $race->load(['championship']);

        $search = $request->string('s')->toString() ?: null;
        $filterChannel = $request->string('channel')->toString() ?: null;
        $filterConfirmed = $request->string('confirmed')->toString() ?: null;

        // Load all participants (unfiltered) for the summary counters
        $allParticipants = $race->participants()->with('payments')->get();

        $totalExpected = $allParticipants->sum(fn ($p) => $p->price()->last());

        $summary = collect(PaymentChannelType::cases())->map(function (PaymentChannelType $channel) use ($allParticipants, $totalExpected) {
            $group = $allParticipants->filter(fn ($p) => $p->payment_channel === $channel);

            return [
                'channel' => $channel,
                'count' => $group->count(),
                'total' => $group->sum(fn ($p) => $p->price()->last()),
                'expected' => $totalExpected,
            ];
        })->push([
            'channel' => null,
            'count' => $allParticipants->filter(fn ($p) => $p->payment_channel === null)->count(),
            'total' => 0,
            'expected' => $totalExpected,
        ]);

        // Build filtered query for the table
        $participants = $race->participants()
            ->with('payments')
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('bib', e($search))
                        ->orWhere('first_name', 'LIKE', e($search).'%')
                        ->orWhere('last_name', 'LIKE', e($search).'%');
                });
            })
            ->when($filterChannel !== null, function ($query) use ($filterChannel) {
                if ($filterChannel === 'none') {
                    $query->whereNull('payment_channel');
                } else {
                    $query->where('payment_channel', (int) $filterChannel);
                }
            })
            ->when($filterConfirmed !== null, function ($query) use ($filterConfirmed) {
                match ($filterConfirmed) {
                    'confirmed' => $query->whereNotNull('payment_confirmed_at'),
                    'unconfirmed' => $query->whereNull('payment_confirmed_at'),
                    default => null,
                };
            })
            ->orderBy('bib', 'asc')
            ->get();

        return view('race.payments', [
            'race' => $race,
            'championship' => $race->championship,
            'participants' => $participants,
            'summary' => $summary,
            'totalExpected' => $totalExpected,
            'search' => $search,
            'filterChannel' => $filterChannel,
            'filterConfirmed' => $filterConfirmed,
        ]);
    }
}
