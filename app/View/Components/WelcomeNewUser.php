<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\Participant;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class WelcomeNewUser extends Component
{
    public bool $show;

    public function __construct()
    {
        $user = auth()->user();

        // TODO: show this form only for driver users

        $this->show = $user !== null
            && Participant::where(function ($q) use ($user) {
                $q->where('claimed_by', $user->id)->orWhere('added_by', $user->id);
            })->doesntExist();
    }

    public function render(): View|Closure|string
    {
        return view('components.welcome-new-user');
    }
}
