<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Order;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Tells the buyer the final amount of an Order priced as a range, and how to pay it.
 */
class OrderPriceSet extends AccountNotification
{
    public function __construct(public Order $order) {}

    public function type(): NotificationType
    {
        return NotificationType::OrderPriceSet;
    }

    public function toMail(User $notifiable): MailMessage
    {
        $mail = $this->mailMessage()
            ->subject(__('The price of your order is set'))
            ->line(__('The organizer set the price of your order :order: :amount.', ['order' => $this->order->ulid, 'amount' => $this->order->amountLabel()]));

        if ($this->order->price_override_reason) {
            $mail->line(__('Reason: :reason', ['reason' => $this->order->price_override_reason]));
        }

        return $mail
            ->line($this->bankTransferLine())
            ->line(__('Use the following text as the bank transfer reason:').' '.$this->order->ulid)
            ->action(__('Upload the payment proof'), route('my-orders.show', $this->order));
    }

    /**
     * @return array{order: string}
     */
    public function toArray(User $notifiable): array
    {
        return ['order' => $this->order->ulid];
    }
}
