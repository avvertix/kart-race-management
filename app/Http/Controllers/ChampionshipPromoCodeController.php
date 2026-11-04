<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\PromoCodes\IssuePromoCodes;
use App\Enums\EntitlementMode;
use App\Enums\PromoCodeStatus;
use App\Models\Championship;
use App\Models\PromoCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The organizer issues, lists and revokes the promo codes of a championship with Entitlements enabled.
 */
class ChampionshipPromoCodeController extends Controller
{
    /**
     * The codes of the championship, newest first.
     */
    public function index(Championship $championship): View
    {
        $this->authorize('viewAny', PromoCode::class);

        abort_unless($championship->entitlements_enabled, 404);

        return view('promo-code.index', [
            'championship' => $championship,
            'promoCodes' => $championship->promoCodes()->latest()->latest('id')->paginate(50),
        ]);
    }

    /**
     * The form to issue one or more codes.
     */
    public function create(Championship $championship): View
    {
        $this->authorize('create', PromoCode::class);

        abort_unless($championship->entitlements_enabled, 404);

        return view('promo-code.create', [
            'championship' => $championship,
        ]);
    }

    /**
     * Issue one or more codes. Balances are entered in euros and stored in cents; a custom code is for a single one.
     */
    public function store(Request $request, Championship $championship, IssuePromoCodes $issuePromoCodes): RedirectResponse
    {
        $this->authorize('create', PromoCode::class);

        abort_unless($championship->entitlements_enabled, 404);

        $validated = $request->validate([
            'mode' => ['required', Rule::enum(EntitlementMode::class)],
            'amount' => ['required', 'numeric', 'min:1', 'max:100000', Rule::when($request->input('mode') === EntitlementMode::Credit->value, 'integer')],
            'count' => ['required', 'integer', 'min:1', 'max:200'],
            'code' => ['nullable', 'string', 'min:4', 'max:20', 'regex:/^[A-Za-z0-9-]+$/', 'prohibited_unless:count,1'],
        ]);

        $mode = EntitlementMode::from($validated['mode']);

        $issued = $issuePromoCodes(
            $championship,
            $request->user(),
            $mode,
            $mode->amountFromInput($validated['amount']),
            (int) $validated['count'],
            $validated['code'] ?? null,
        );

        return to_route('championships.promo-codes.index', $championship)
            ->with('flash.banner', trans_choice(':count promo code issued.|:count promo codes issued.', $issued->count(), ['count' => $issued->count()]));
    }

    /**
     * Revoke an unclaimed code: nobody can claim it anymore.
     */
    public function revoke(PromoCode $promoCode): RedirectResponse
    {
        $this->authorize('revoke', $promoCode);

        abort_unless($promoCode->championship->entitlements_enabled, 404);

        DB::transaction(function () use ($promoCode) {
            $locked = PromoCode::query()->whereKey($promoCode->getKey())->lockForUpdate()->firstOrFail();

            abort_unless($locked->status === PromoCodeStatus::Unclaimed, 403);

            $locked->update(['status' => PromoCodeStatus::Revoked]);
        });

        return to_route('championships.promo-codes.index', $promoCode->championship)
            ->with('flash.banner', __('Promo code :code revoked.', ['code' => $promoCode->code]));
    }
}
