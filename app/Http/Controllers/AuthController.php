<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Project;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthController extends Controller
{
    /** The public home page. It also holds the login card, like the template. */
    public function login()
    {
        return Inertia::render('Home', [
            'stats' => [
                'projects' => Project::approved()->count(),
                'years' => Project::approved()->distinct()->count('academic_year'),
            ],
            'announcement' => Announcement::latest()->value('message'),
            // Demo buttons only appear when DEMO_LOGIN=true in .env (turn it off for real use).
            'demoAccounts' => config('app.demo_login') ? [
                ['label' => 'Student', 'email' => 'juan.delacruz@olfu.edu.ph'],
                ['label' => 'Coordinator', 'email' => 'msantos@olfu.edu.ph'],
                ['label' => 'Administrator', 'email' => 'jcalim@olfu.edu.ph'],
            ] : [],
        ]);
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required']]);

        // SECURITY: 5 wrong tries per email + IP, then wait a minute.
        $key = Str::lower($credentials['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Too many attempts. Try again in '.RateLimiter::availableIn($key).' seconds.',
            ]);
        }

        if (! Auth::attempt([...$credentials, 'is_active' => true], $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            $inactive = User::where('email', $credentials['email'])->where('is_active', false)->exists();
            throw ValidationException::withMessages([
                'email' => $inactive ? 'This account has been deactivated.' : 'The email or password is incorrect.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function register()
    {
        return Inertia::render('Auth/Register');
    }

    /** Self-registration always creates a student. Staff accounts are made by the admin. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);
        $user = User::create($data); // `role` is not fillable, so it is always the default: student
        Activity::log('user.registered', "{$user->name} created a student account", $user);
        Auth::login($user);
        $request->session()->regenerate();

        return $this->toWithToast('dashboard', [], 'Welcome! Your account is ready.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
