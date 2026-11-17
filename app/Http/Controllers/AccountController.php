<?php

namespace App\Http\Controllers;

use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

/** My Account: change your name and password. Role and status are read-only here. */
class AccountController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();

        return Inertia::render('Account/Edit', [
            'roleLabel' => config("repository.roles.{$user->role}"),
            'memberSince' => $user->created_at?->format('F j, Y'),
            'projectCount' => $user->projects()->count(),
        ]);
    }

    public function update(Request $request)
    {
        // SECURITY: only `name` is read. Sending role=administrator here does nothing.
        $data = $request->validate(['name' => ['required', 'string', 'max:255']]);
        $request->user()->update(['name' => $data['name']]);

        return $this->backWithToast('Profile updated.');
    }

    public function password(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], ['current_password.current_password' => 'Your current password is incorrect.']);

        $request->user()->update(['password' => $data['password']]);
        Activity::log('user.password_changed', 'Changed their password');

        return $this->backWithToast('Password changed.');
    }
}
