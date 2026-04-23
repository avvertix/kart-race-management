<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Fortify\CreateNewUserWithRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Laravel\Jetstream\Jetstream;
use Laravel\Jetstream\Rules\Role;

class UserController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(User::class, 'user');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');

        $users = User::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->get();

        return view('user.index', [
            'users' => $users,
            'search' => $search,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('user.create', [
            'roles' => $this->availableRoles(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, CreateNewUserWithRole $createNewUserWithRole)
    {
        $user = $createNewUserWithRole->create($request->all());

        return redirect()->route('users.index')
            ->with('flash.banner', __(':name created.', ['name' => $user->name]));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        return view('user.edit', [
            'user' => $user,
            'roles' => $this->availableRoles(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', 'string', new Role],
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ]);

        return redirect()->route('users.index')
            ->with('flash.banner', __(':name updated.', ['name' => $validated['name']]));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        if ($user->getKey() === auth()->id()) {
            return redirect()->route('users.edit', $user)
                ->with('flash.banner', __('You cannot delete your own account.'))
                ->with('flash.bannerStyle', 'danger');
        }

        if ($user->isAdmin() && User::where('role', 'admin')->count() <= 1) {
            return redirect()->route('users.edit', $user)
                ->with('flash.banner', __('You cannot delete the last administrator.'))
                ->with('flash.bannerStyle', 'danger');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('flash.banner', __(':name deleted.', ['name' => $user->name]));
    }

    /**
     * Get available roles for users.
     *
     * @return array<string, string>
     */
    protected function availableRoles(): array
    {
        return collect(Jetstream::$roles)
            ->mapWithKeys(fn ($role) => [$role->key => $role->name])
            ->all();
    }
}
