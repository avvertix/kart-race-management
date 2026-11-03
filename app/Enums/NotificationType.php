<?php

declare(strict_types=1);

namespace App\Enums;

use App\Notifications\AccountNotification;
use App\Notifications\LicenceEmitted;
use App\Notifications\LicenceRequestChangesRequested;
use App\Notifications\LicenceRequestReceived;
use App\Notifications\LicenceRequestRejected;
use App\Notifications\LicenceRequestSubmitted;
use App\Notifications\LicenceRequestVerified;
use App\Notifications\OrderPriceSet;

/**
 * The notifications a User can receive, each by email and in the app. The value keys the User's email preferences.
 */
enum NotificationType: string
{
    case LicenceRequestReceived = 'licence_request_received';

    case LicenceRequestChangesRequested = 'licence_request_changes_requested';

    case LicenceRequestVerified = 'licence_request_verified';

    case LicenceRequestRejected = 'licence_request_rejected';

    case LicenceEmitted = 'licence_emitted';

    case OrderPriceSet = 'order_price_set';

    /**
     * Sent to the organizers when a request is submitted, also after changes.
     */
    case LicenceRequestSubmitted = 'licence_request_submitted';

    /**
     * The type of a notification kept in the app, from the class stored with it.
     */
    public static function fromNotificationClass(string $class): ?self
    {
        return collect(self::cases())->first(fn (self $type) => $type->notificationClass() === $class);
    }

    /**
     * @return class-string<AccountNotification>
     */
    public function notificationClass(): string
    {
        return match ($this) {
            self::LicenceRequestReceived => LicenceRequestReceived::class,
            self::LicenceRequestChangesRequested => LicenceRequestChangesRequested::class,
            self::LicenceRequestVerified => LicenceRequestVerified::class,
            self::LicenceRequestRejected => LicenceRequestRejected::class,
            self::LicenceEmitted => LicenceEmitted::class,
            self::OrderPriceSet => OrderPriceSet::class,
            self::LicenceRequestSubmitted => LicenceRequestSubmitted::class,
        };
    }

    /**
     * Whether it is sent to the organizers instead of the applicant.
     */
    public function isForOrganizers(): bool
    {
        return $this === self::LicenceRequestSubmitted;
    }

    /**
     * The human readable name of the notification.
     */
    public function label(): string
    {
        return trans("notification-types.label.{$this->value}");
    }

    /**
     * When the notification is sent.
     */
    public function description(): string
    {
        return trans("notification-types.description.{$this->value}");
    }
}
