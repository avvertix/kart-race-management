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
