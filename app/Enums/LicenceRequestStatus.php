<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Where a Licence Request is in its lifecycle, see docs/reference/lifecycles.md#licence-request.
 */
enum LicenceRequestStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case InReview = 'in_review';
    case ChangesRequested = 'changes_requested';
    case Forwarded = 'forwarded';
    case Emitted = 'emitted';
    case Rejected = 'rejected';

    /**
     * The statuses in which the organizer checks sections and documents and can forward the request.
     *
     * @return array<int, self>
     */
    public static function underReview(): array
    {
        return [self::Submitted, self::InReview];
    }

    /**
     * The statuses in which the organizer can set the amount and review the payment proof, also while waiting for fixes.
     *
     * @return array<int, self>
     */
    public static function underPaymentReview(): array
    {
        return [...self::underReview(), self::ChangesRequested];
    }

    /**
     * The statuses from which the organizer can reject the request.
     *
     * @return array<int, self>
     */
    public static function rejectable(): array
    {
        return [...self::underReview(), self::Forwarded];
    }

    /**
     * The statuses listed for the organizer by default: the requests not closed nor still a draft.
     *
     * @return array<int, self>
     */
    public static function toProcess(): array
    {
        return [...self::underReview(), self::ChangesRequested, self::Forwarded];
    }

    /**
     * The human readable name of the status.
     */
    public function label(): string
    {
        return trans("licences.request_status.{$this->value}");
    }

    /**
     * Whether the requester can change the request: freely as a draft, only the flagged parts after a change request.
     */
    public function isEditableByRequester(): bool
    {
        return in_array($this, [self::Draft, self::ChangesRequested]);
    }

    /**
     * Whether the request is over: emitted or rejected.
     */
    public function isClosed(): bool
    {
        return in_array($this, [self::Emitted, self::Rejected], true);
    }
}
