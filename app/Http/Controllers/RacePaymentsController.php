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

        $participants = $race->participants()
            ->with('payments')
            ->orderBy('bib', 'asc')
            ->get();

        $totalExpected = $participants->sum(fn ($p) => $p->price()->last());

        $summary = collect(PaymentChannelType::cases())->map(function (PaymentChannelType $channel) use ($participants, $totalExpected) {
            $group = $participants->filter(fn ($p) => $p->payment_channel === $channel);

            return [
                'channel' => $channel,
                'count' => $group->count(),
                'total' => $group->sum(fn ($p) => $p->price()->last()),
                'expected' => $totalExpected,
            ];
        })->push([
            'channel' => null,
            'count' => $participants->filter(fn ($p) => $p->payment_channel === null)->count(),
            'total' => 0,
            'expected' => $totalExpected,
        ]);

        return view('race.payments', [
            'race' => $race,
            'championship' => $race->championship,
            'participants' => $participants,
            'summary' => $summary,
            'totalExpected' => $totalExpected,
        ]);
    }
}
