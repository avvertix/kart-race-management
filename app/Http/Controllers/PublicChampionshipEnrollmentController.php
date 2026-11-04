<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ProductKind;
use App\Models\Championship;
use App\Models\Product;
use Illuminate\View\View;

/**
 * The public page of a championship that drivers can enroll in.
 */
class PublicChampionshipEnrollmentController extends Controller
{
    public function show(Championship $championship): View
    {
        $settings = $championship->enrollment;

        abort_unless($settings->enabled, 404);

        $product = $settings->isFree() ? null : Product::query()
            ->where('kind', ProductKind::Enrollment->value)
            ->where('championship_id', $championship->getKey())
            ->find($settings->product_id);

        return view('public-championship.enrollment', [
            'championship' => $championship,
            'settings' => $settings,
            'price' => $product?->orderablePrice(),
        ]);
    }
}
