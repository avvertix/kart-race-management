<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Participant;
use App\Models\User;
use Laravel\Jetstream\Contracts\DeletesUsers;

class DeleteUser implements DeletesUsers
{
    /**
     * Delete the given user.
     *
     * @param  User  $user
     */
    public function delete($user): void
    {
        Participant::where('added_by', $user->id)->update(['added_by' => null]);
        Participant::where('claimed_by', $user->id)->update(['claimed_by' => null]);

        $user->delete();
    }
}
