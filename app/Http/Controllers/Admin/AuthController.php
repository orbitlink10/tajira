<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TotpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (auth()->check() && auth()->user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function login(Request $request, AuditLogger $audit)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', strtolower($data['email']))->first();

        if (! $user || ! $user->isAdmin() || ! $user->is_active) {
            throw ValidationException::withMessages(['email' => 'These credentials do not match an active administrator account.']);
        }

        if (! Auth::attempt(['email' => strtolower($data['email']), 'password' => $data['password'], 'is_active' => true], $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'These credentials do not match an active administrator account.']);
        }

        $request->session()->regenerate();
        $request->session()->put('admin_mfa_passed', ! $user->mfa_enabled);
        $user->forceFill(['last_login_at' => now()])->save();
        $audit->log('admin.login', $user);

        if ($user->mfa_enabled) {
            return redirect()->route('admin.mfa.challenge');
        }

        if (config('tajira.security.require_admin_mfa')) {
            return redirect()->route('admin.mfa.setup')->with('warning', 'Set up two-factor authentication to continue.');
        }

        return redirect()->intended(route('admin.dashboard'));
    }

    public function showChallenge()
    {
        if (! auth()->check() || ! auth()->user()->isAdmin()) {
            return redirect()->route('admin.login');
        }

        if (! auth()->user()->mfa_enabled || session('admin_mfa_passed') === true) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.mfa-challenge');
    }

    public function verifyChallenge(Request $request, TotpService $totp, AuditLogger $audit)
    {
        $request->validate(['code' => ['required', 'string', 'max:10']]);
        $user = $request->user();

        if (! $user->mfa_enabled || ! $user->mfa_secret || ! $totp->verify($user->mfa_secret, $request->input('code'))) {
            throw ValidationException::withMessages(['code' => 'The authentication code is invalid or has expired.']);
        }

        $request->session()->put('admin_mfa_passed', true);
        $audit->log('admin.mfa.verified', $user);

        return redirect()->intended(route('admin.dashboard'))->with('success', 'Two-factor authentication complete.');
    }

    public function showSetup(Request $request, TotpService $totp)
    {
        $user = $request->user();

        if ($user->mfa_enabled) {
            return view('admin.auth.mfa-manage', ['user' => $user]);
        }

        $secret = $request->session()->get('admin_mfa_setup_secret');

        if (! $secret) {
            $secret = $totp->generateSecret();
            $request->session()->put('admin_mfa_setup_secret', $secret);
        }

        $uri = $totp->provisioningUri($secret, $user->email, setting('business.name', 'Tajira'));

        return view('admin.auth.mfa-setup', compact('user', 'secret', 'uri'));
    }

    public function enableMfa(Request $request, TotpService $totp, AuditLogger $audit)
    {
        $request->validate(['code' => ['required', 'string', 'max:10']]);
        $secret = $request->session()->get('admin_mfa_setup_secret');

        if (! $secret || ! $totp->verify($secret, $request->input('code'))) {
            throw ValidationException::withMessages(['code' => 'The authentication code is invalid. Check your authenticator app and try again.']);
        }

        $user = $request->user();
        $user->forceFill(['mfa_secret' => $secret, 'mfa_enabled' => true])->save();
        $request->session()->forget('admin_mfa_setup_secret');
        $request->session()->put('admin_mfa_passed', true);
        $audit->log('admin.mfa.enabled', $user);

        return redirect()->route('admin.dashboard')->with('success', 'Two-factor authentication is enabled.');
    }

    public function disableMfa(Request $request, TotpService $totp, AuditLogger $audit)
    {
        $request->validate([
            'password' => ['required', 'string'],
            'code' => ['required', 'string', 'max:10'],
        ]);

        $user = $request->user();

        if (! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages(['password' => 'The password is incorrect.']);
        }

        if (! $user->mfa_secret || ! $totp->verify($user->mfa_secret, $request->input('code'))) {
            throw ValidationException::withMessages(['code' => 'The authentication code is invalid.']);
        }

        $user->forceFill(['mfa_secret' => null, 'mfa_enabled' => false])->save();
        $request->session()->put('admin_mfa_passed', true);
        $audit->log('admin.mfa.disabled', $user);

        return redirect()->route('admin.mfa.setup')->with('success', 'Two-factor authentication has been disabled.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('success', 'Signed out of the administration area.');
    }
}
