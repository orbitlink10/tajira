<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (auth()->check()) {
            return auth()->user()->isAdmin()
                ? redirect()->route('admin.dashboard')
                : redirect()->route('account.dashboard');
        }

        return view('storefront.auth.login', ['meta' => $this->privateMeta('Sign in', route('login'))]);
    }

    public function login(Request $request, AuditLogger $audit)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', strtolower($credentials['email']))->first();

        if (! $user || ! $user->is_active || ! Auth::attempt([
            'email' => strtolower($credentials['email']),
            'password' => $credentials['password'],
            'is_active' => true,
        ], $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match an active account.',
            ]);
        }

        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();
        $audit->log('auth.login', $user, [], ['role' => $user->role]);

        return $user->isAdmin()
            ? redirect()->intended(route('admin.dashboard'))
            : redirect()->intended(route('account.dashboard'));
    }

    public function showRegister()
    {
        if (auth()->check()) {
            return redirect()->route('account.dashboard');
        }

        return view('storefront.auth.register', ['meta' => $this->privateMeta('Create an account', route('register'))]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:140'],
            'email' => ['required', 'email', 'max:180', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30'],
            'password' => ['required', 'confirmed', PasswordRule::min(10)->mixedCase()->numbers()],
            'terms' => ['accepted'],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'phone' => $data['phone'],
            'password' => $data['password'],
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('account.dashboard')->with('success', 'Welcome to Tajira. Your account is ready.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'You have signed out.');
    }

    public function showForgot()
    {
        return view('storefront.auth.forgot-password', ['meta' => $this->privateMeta('Reset your password', route('password.request'))]);
    }

    public function sendReset(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? back()->with('success', 'If that email is registered, a reset link has been sent.')
            : back()->with('error', 'We could not send a reset link for that email.');
    }

    public function showReset(Request $request, string $token)
    {
        return view('storefront.auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
            'meta' => $this->privateMeta('Choose a new password', route('password.reset', $token)),
        ]);
    }

    public function reset(Request $request)
    {
        $data = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(10)->mixedCase()->numbers()],
        ]);

        $status = Password::reset($data, function (User $user, string $password) {
            $user->forceFill([
                'password' => $password,
                'remember_token' => Str::random(60),
            ])->save();
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        return redirect()->route('login')->with('success', 'Your password has been reset. Please sign in.');
    }

    private function privateMeta(string $title, string $canonical): array
    {
        return [
            'title' => $title.' | '.setting('business.name', 'Tajira'),
            'description' => 'Manage your Tajira account.',
            'canonical' => $canonical,
            'robots' => 'noindex,nofollow',
            'image' => asset('images/og-default.png'),
            'type' => 'website',
            'previous' => null,
            'next' => null,
            'gsc_verification' => null,
        ];
    }
}
