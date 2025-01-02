<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\CommunicationMessage;
use Closure;
use Illuminate\View\Component;

class BroadcastCommunications extends Component
{
    public $communications;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->communications = CommunicationMessage::query()
            ->active()
            ->when($this->getUserRole(), function ($query, $role) {
                $query->targetUser($role);
            })
            ->get();
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|Closure|string
     */
    public function render()
    {
        return view('components.broadcast-communications');
    }

    protected function getUserRole()
    {
        $user = auth()->user();

        if (is_null($user)) {
            return null;
        }

        return $user->userRole()?->key;
    }
}
