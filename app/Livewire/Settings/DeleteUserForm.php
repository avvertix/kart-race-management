<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Actions\DeleteUser;
use App\Concerns\PasswordValidationRules;
use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DeleteUserForm extends Component
{
    use PasswordValidationRules;

    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout, DeleteUser $deleteUser): void
    {
        $this->validate([
            'password' => $this->currentPasswordRules(),
        ]);

        $deleteUser(tap(Auth::user(), $logout(...)));

        $this->redirect('/', navigate: true);
    }
}
