<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Order;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Tells the buyer the organizer could not confirm the payment proof, so they upload a new one.
 */
class OrderPaymentRejected extends AccountNotification
{
    public function __construct(public Order $order) {}

    public function type(): NotificationType
    {
        return NotificationType::OrderPaymentRejected;
    }

    public function toMail(User $notifiable): MailMessage
    {
        return $this->mailMessage()
            ->subject(__('Your payment proof was not accepted'))
            ->line(__('The organizer could not confirm the payment proof of :order. Upload a new one.', ['order' => $this->order->lines()->pluck('description')->join(', ') ?: $this->order->ulid]))
            ->action(__('Upload the payment proof'), $this->openUrl());
    }

    /**
     * @return array{order: string}
     */
    public function toArray(User $notifiable): array
    {
        return ['order' => $this->order->ulid];
    }
}
