<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\SubmitPaymentProof;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MyOrderController extends Controller
{
    /**
     * The order of the signed-in user, with what to pay and the payment proof upload.
     */
    public function show(Request $request, Order $order): View
    {
        $this->authorize('view', $order);

        $order->load(['lines.price', 'latestPayment']);

        $request->user()->markNotificationsAsReadAbout($order);

        return view('my-order.show', [
            'order' => $order,
        ]);
    }

    /**
     * Upload the payment proof.
     */
    public function pay(Request $request, Order $order, SubmitPaymentProof $submitPaymentProof): RedirectResponse
    {
        $this->authorize('pay', $order);

        $request->validate([
            'proof' => 'required|file',
        ]);

        $submitPaymentProof($order, $request->file('proof'));

        return redirect()->route('my-orders.show', $order)
            ->with('flash.banner', __('Payment proof uploaded. The organizer will confirm it.'));
    }
}
