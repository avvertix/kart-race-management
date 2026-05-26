<?php

declare(strict_types=1);

namespace App\Actions\Jetstream;

use App\Models\Participant;
use Laravel\Jetstream\Contracts\DeletesUsers;

class DeleteUser implements DeletesUsers
{
    /**
     * Delete the given user.
     *
     * @param  mixed  $user
     * @return void
     */
    public function delete($user)
    {
        Participant::where('added_by', $user->id)->update(['added_by' => null]);
        Participant::where('claimed_by', $user->id)->update(['claimed_by' => null]);

        $user->deleteProfilePhoto();
        $user->tokens->each->delete();
        $user->delete();
    }
}
