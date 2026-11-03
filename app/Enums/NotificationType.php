<?php

declare(strict_types=1);

namespace App\Enums;

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
