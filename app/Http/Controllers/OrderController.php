<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Orders\ConfirmOrderPayment;
use App\Actions\Orders\RejectOrderPayment;
use App\Actions\Orders\SetOrderPrice;
use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    /**
     * The orders, oldest first, optionally of one status.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Order::class);

        $request->validate([
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
        ]);

        $status = $request->enum('status', OrderStatus::class);

        return view('order.index', [
            'status' => $status,
            'orders' => Order::query()
                ->with(['user', 'lines'])
                ->when($status, fn ($query) => $query->where('status', $status))
                ->oldest()
                ->paginate(50)
                ->withQueryString(),
        ]);
    }

    /**
     * The order detail, with the price to set and the payment proof to confirm or reject.
     */
    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        $order->load(['user', 'lines.price', 'lines.product', 'latestPayment']);

        return view('order.show', [
            'order' => $order,
        ]);
    }

    /**
     * Set the final amount. The amount is in euro.
     */
    public function price(Request $request, Order $order, SetOrderPrice $setOrderPrice): RedirectResponse
    {
        $this->authorize('update', $order);

        $validated = $request->validate([
            'amount' => 'required|numeric|decimal:0,2|min:0',
            'price_override_reason' => 'nullable|string|max:1000',
        ]);

        $setOrderPrice($order, $request->user(), (int) round(((float) $validated['amount']) * 100), $validated['price_override_reason'] ?? null);

        return redirect()->route('orders.show', $order)
            ->with('flash.banner', __('Price set.'));
    }

    /**
     * Confirm the submitted payment proof.
     */
    public function confirmPayment(Request $request, Order $order, ConfirmOrderPayment $confirmOrderPayment): RedirectResponse
    {
        $this->authorize('update', $order);

        $confirmOrderPayment($order, $request->user());

        return redirect()->route('orders.show', $order)
            ->with('flash.banner', __('Payment confirmed.'));
    }

    /**
     * Reject the submitted payment proof, the user can upload a new one.
     */
    public function rejectPayment(Order $order, RejectOrderPayment $rejectOrderPayment): RedirectResponse
    {
        $this->authorize('update', $order);

        $rejectOrderPayment($order);

        return redirect()->route('orders.show', $order)
            ->with('flash.banner', __('Payment rejected.'));
    }
}
