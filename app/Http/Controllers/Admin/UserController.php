<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('users.manage');
        $users = User::query()
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->query('role')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.trim((string) $request->query('q')).'%';
                $q->where(fn ($inner) => $inner->where('name', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $this->authorize('users.manage');

        return view('admin.users.form', ['user' => new User(['is_active' => true, 'role' => User::ROLE_ORDER_MANAGER])]);
    }

    public function store(Request $request, AuditLogger $audit)
    {
        $this->authorize('users.manage');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:140'],
            'email' => ['required', 'email', 'max:180', 'unique:users,email'],
            'role' => ['required', Rule::in(User::adminRoles())],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'role' => $data['role'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'is_active' => $request->boolean('is_active'),
        ]);

        $audit->log('admin-user.created', $user, [], $user->only(['name', 'email', 'role', 'is_active']));

        return redirect()->route('admin.users.index')->with('success', 'Administrator created. Ask them to set up MFA on first sign-in.');
    }

    public function edit(User $user)
    {
        $this->authorize('users.manage');

        return view('admin.users.form', compact('user'));
    }

    public function update(Request $request, User $user, AuditLogger $audit)
    {
        $this->authorize('users.manage');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:140'],
            'email' => ['required', 'email', 'max:180', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in($user->role === User::ROLE_SUPER_ADMIN ? User::adminRoles() : User::adminRoles())],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($request->user()->id === $user->id && ($data['role'] !== User::ROLE_SUPER_ADMIN)) {
            return back()->with('error', 'You cannot remove your own super administrator role.');
        }

        $old = $user->only(['name', 'email', 'role', 'phone', 'is_active']);
        $payload = [
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'role' => $data['role'],
            'phone' => $data['phone'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ];

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $user->update($payload);
        $audit->log('admin-user.updated', $user, $old, $user->only(['name', 'email', 'role', 'phone', 'is_active']));

        return redirect()->route('admin.users.index')->with('success', 'Administrator updated.');
    }

    public function deactivate(Request $request, User $user, AuditLogger $audit)
    {
        $this->authorize('users.manage');

        if ($request->user()->id === $user->id) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        if ($user->role === User::ROLE_SUPER_ADMIN) {
            $activeSupers = User::query()->where('role', User::ROLE_SUPER_ADMIN)->where('is_active', true)->count();

            if ($activeSupers <= 1) {
                return back()->with('error', 'At least one active super administrator is required.');
            }
        }

        $user->update(['is_active' => false]);
        $audit->log('admin-user.deactivated', $user);

        return back()->with('success', 'Account deactivated. Sessions will be blocked on the next request.');
    }
}
