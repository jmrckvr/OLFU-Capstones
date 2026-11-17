<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/** Admin: user accounts. Routes are guarded by role:administrator. */
class UserController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Users', [
            'users' => User::withCount('projects')->orderBy('name')
                ->get(['id', 'name', 'email', 'role', 'is_active', 'created_at']),
            'roles' => config('repository.roles'),
        ]);
    }

    /** Create an account (the only way to make a coordinator or another admin). */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users'],
            'role' => ['required', Rule::in(array_keys(config('repository.roles')))],
        ]);
        $password = $this->temporaryPassword();
        $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => $password]);
        $user->role = $data['role'];
        $user->save();
        Activity::log('user.created', "Created the account {$user->email} ({$data['role']})");

        // Shown once in a dialog, then gone (flash data is never stored in browser history).
        Inertia::flash('password', ['email' => $user->email, 'password' => $password, 'title' => 'Account created']);

        return back();
    }

    /** Change role or active status. */
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'role' => ['sometimes', Rule::in(array_keys(config('repository.roles')))],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        // SECURITY: an admin can never lock themselves out.
        if ($user->is($request->user())) {
            if (array_key_exists('is_active', $data) && ! $data['is_active']) {
                throw ValidationException::withMessages(['is_active' => 'You cannot deactivate your own account.']);
            }
            if (array_key_exists('role', $data) && $data['role'] !== 'administrator') {
                throw ValidationException::withMessages(['role' => 'You cannot remove your own administrator role.']);
            }
        }

        $before = $user->only('role', 'is_active');
        $user->forceFill($data)->save();

        if (($data['is_active'] ?? null) !== null && $before['is_active'] !== $user->is_active) {
            Activity::log($user->is_active ? 'user.activated' : 'user.deactivated', ($user->is_active ? 'Activated ' : 'Deactivated ').$user->email);

            return $this->backWithToast($user->is_active ? "{$user->name} can sign in again." : "{$user->name} has been deactivated.");
        }
        if (($data['role'] ?? null) && $before['role'] !== $user->role) {
            Activity::log('user.role', "Changed {$user->email} to ".config("repository.roles.{$user->role}"));
        }

        return $this->backWithToast('Role updated.');
    }

    /** Sets a new temporary password and shows it once to the admin (no email service needed). */
    public function resetPassword(User $user)
    {
        $password = $this->temporaryPassword();
        $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
        DB::table('sessions')->where('user_id', $user->id)->delete(); // sign them out everywhere
        Activity::log('user.password_reset', "Reset the password of {$user->email}");

        Inertia::flash('password', ['email' => $user->email, 'password' => $password, 'title' => 'Password reset']);

        return back();
    }

    private function temporaryPassword(): string
    {
        return Str::password(12, symbols: false);
    }
}
