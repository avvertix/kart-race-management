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
    case Verified = 'verified';
    case Forwarded = 'forwarded';
    case Emitted = 'emitted';
    case Rejected = 'rejected';

    /**
     * The statuses in which the organizer reviews the request: marks documents as viewed, comments and finishes the review.
     *
     * @return array<int, self>
     */
    public static function underReview(): array
    {
        return [self::Submitted, self::InReview];
    }

    /**
     * The statuses after a review without changes to ask: the organizer reviews the payment and emits the licence.
     *
     * @return array<int, self>
     */
    public static function verified(): array
    {
        return [self::Verified, self::Forwarded];
    }

    /**
     * The statuses from which the organizer can reject the request.
     *
     * @return array<int, self>
     */
    public static function rejectable(): array
    {
        return [...self::underReview(), ...self::verified()];
    }

    /**
     * The statuses listed for the organizer by default: the requests not closed nor still a draft.
     *
     * @return array<int, self>
     */
    public static function toProcess(): array
    {
        return [...self::underReview(), self::ChangesRequested, ...self::verified()];
    }

    /**
     * The human readable name of the status.
     */
    public function label(): string
    {
        return trans("licences.request_status.{$this->value}");
    }

    /**
     * The name of the status as the requester sees it: a forwarded request was sent to the provider.
     */
    public function requesterLabel(): string
    {
        return $this === self::Forwarded ? trans('licences.request_status.sent_to_provider') : $this->label();
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
