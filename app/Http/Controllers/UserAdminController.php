<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Committee;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Admin-only (gate `manage-users`): assign roles and committees to accounts. */
class UserAdminController extends Controller
{
    public function index(Request $request): View
    {
        $role = UserRole::tryFrom((string) $request->query('role'));

        $users = User::with('committee')
            ->when($role, fn ($q) => $q->where('role', $role->value))
            ->when($request->query('q'), fn ($q, $term) => $q->where(fn ($w) => $w->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'committees' => Committee::orderBy('name')->get(),
            'roleFilter' => $role,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', Rule::enum(UserRole::class)],
            'committee_id' => ['nullable', 'exists:committees,id'],
        ]);

        if ($user->is($request->user()) && $data['role'] !== $user->role->value) {
            return back()->withErrors(['role' => 'You cannot change your own role.']);
        }

        $user->update(['role' => $data['role'], 'committee_id' => $data['committee_id'] ?: null]);

        return back()->with('status', "{$user->name} updated.");
    }
}
