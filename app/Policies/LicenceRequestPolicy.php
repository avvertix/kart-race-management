<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\LicenceRequestStatus;
use App\Models\LicenceRequest;
use App\Models\User;

class LicenceRequestPolicy
{
    /**
     * Determine whether the user can list all licence requests.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('licence-request:list');
    }

    /**
     * Determine whether the user can view the request, its documents and forms. Users only see their own requests.
     */
    public function view(User $user, LicenceRequest $licenceRequest): bool
    {
        if ($user->hasPermission('licence-request:list')) {
            return $user->hasPermission('licence-request:view');
        }

        return $user->hasPermission('licence-request:view') && $this->isRequester($user, $licenceRequest);
    }

    /**
     * Determine whether the user can process requests as the organizer: review, price, forward, emit and reject them.
     */
    public function review(User $user, LicenceRequest $licenceRequest): bool
    {
        return $user->hasPermission('licence-request:list') && $user->hasPermission('licence-request:update');
    }

    /**
     * Determine whether the user can start licence requests.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('licence-request:create');
    }

    /**
     * Determine whether the user can fill in the request: their own drafts, or the flagged parts after a change request.
     */
    public function update(User $user, LicenceRequest $licenceRequest): bool
    {
        return $user->hasPermission('licence-request:update')
            && $this->isRequester($user, $licenceRequest)
            && $licenceRequest->status->isEditableByRequester();
    }

    /**
     * Determine whether the user can upload a payment proof: their own requests, until emitted or rejected.
     */
    public function pay(User $user, LicenceRequest $licenceRequest): bool
    {
        return $user->hasPermission('licence-request:update')
            && $this->isRequester($user, $licenceRequest)
            && ! $licenceRequest->status->isClosed();
    }

    /**
     * Determine whether the user can delete the request: only their own drafts.
     */
    public function delete(User $user, LicenceRequest $licenceRequest): bool
    {
        return $user->hasPermission('licence-request:update')
            && $this->isRequester($user, $licenceRequest)
            && $licenceRequest->status === LicenceRequestStatus::Draft;
    }

    private function isRequester(User $user, LicenceRequest $licenceRequest): bool
    {
        return $licenceRequest->user_id === $user->getKey();
    }
}
