<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Order;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Tells the buyer the organizer confirmed the payment of the Order.
 */
class OrderPaymentConfirmed extends AccountNotification
{
    public function __construct(public Order $order) {}

    public function type(): NotificationType
    {
        return NotificationType::OrderPaymentConfirmed;
    }

    public function toMail(User $notifiable): MailMessage
    {
        $mail = $this->mailMessage()
            ->subject(__('Your payment is confirmed'))
            ->line(__('The organizer confirmed the payment of :order.', ['order' => $this->order->lines()->pluck('description')->join(', ') ?: $this->order->ulid]));

        if ($this->order->canStartLicenceRenewal()) {
            $mail->line(__('It includes a licence renewal: start the licence request, it asks for no payment.'));
        }

        return $mail->action(__('View'), $this->openUrl());
    }

    /**
     * @return array{order: string}
     */
    public function toArray(User $notifiable): array
    {
        return ['order' => $this->order->ulid];
    }
}
