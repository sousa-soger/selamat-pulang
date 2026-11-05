<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountController extends Controller
{
    public function updateProfile(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => 'required|string|max:120', 'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048']);
        $user = $request->user();
        $user->name = $data['name'];
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'local');
            abort_unless($path, 500);
            $old = $user->avatar_path;
            $user->avatar_path = $path;
        }
        $user->save();
        if (isset($old) && $old) {
            Storage::disk('local')->delete($old);
        }

        return back()->with('status', 'Profile updated.');
    }

    public function avatar(Request $request): StreamedResponse
    {
        abort_unless($request->user()->avatar_path, 404);

        return Storage::disk('local')->response($request->user()->avatar_path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        $data['email'] = strtolower($data['email']);
        if (! Auth::attempt($data, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'The email or password is incorrect.'])->onlyInput('email');
        }
        $request->session()->regenerate();
        $request->user()->promoteDesignatedAdmin();

        return redirect()->intended(route('account'));
    }

    public function register(Request $request): RedirectResponse
    {
        $request->merge(['email' => strtolower((string) $request->input('email'))]);
        $data = $request->validate(['name' => 'required|string|max:120', 'email' => 'required|email|max:255|unique:users', 'password' => ['required', 'confirmed', PasswordRule::min(12)]]);
        $user = User::create($data);
        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('account')->with('status', 'Your free profile is ready. Please verify your email.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function show(Request $request): View
    {
        return view('accounts.profile', ['user' => $request->user(), 'organizations' => $request->user()->organizations()->with('owner')->get()]);
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();
        $request->user()->promoteDesignatedAdmin();

        return redirect()->route('account')->with('status', 'Email verified.');
    }

    public function resend(Request $request): RedirectResponse
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->user()->sendEmailVerificationNotification();
        }

        return back()->with('status', 'Verification link sent.');
    }

    public function forgot(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);
        Password::sendResetLink(['email' => strtolower($request->email)]);

        return back()->with('status', 'If that email has an account, a password reset link has been sent.');
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate(['token' => 'required', 'email' => 'required|email', 'password' => ['required', 'confirmed', PasswordRule::min(12)]]);
        $status = Password::reset($data, function (User $user, string $password) {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });

        return $status === Password::PasswordReset ? redirect()->route('login')->with('status', __($status)) : back()->withErrors(['email' => __($status)]);
    }

    public function google(Request $request): RedirectResponse
    {
        if (! config('accounts.google_client_id') || ! config('accounts.google_client_secret')) {
            return redirect()->route('login')->withErrors(['google' => 'Google sign-in is not configured yet. Please use email and password.']);
        }
        $state = Str::random(64);
        $request->session()->put('google_state', $state);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => config('accounts.google_client_id'), 'redirect_uri' => config('accounts.google_redirect_uri') ?: route('google.callback'),
            'response_type' => 'code', 'scope' => 'openid email profile', 'state' => $state, 'prompt' => 'select_account',
        ]));
    }

    public function callback(Request $request): RedirectResponse
    {
        $state = $request->session()->pull('google_state');
        if (! $state || ! is_string($request->state) || ! hash_equals($state, $request->state) || ! $request->filled('code')) {
            return redirect()->route('login')->withErrors(['google' => 'Google sign-in was cancelled or expired. Please try again.']);
        }
        try {
            $token = Http::asForm()->timeout(15)->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('accounts.google_client_id'), 'client_secret' => config('accounts.google_client_secret'),
                'redirect_uri' => config('accounts.google_redirect_uri') ?: route('google.callback'), 'grant_type' => 'authorization_code', 'code' => $request->code,
            ])->throw()->json('access_token');
            if (! is_string($token) || $token === '') {
                throw new \RuntimeException('Missing token');
            }
            $profile = Http::withToken($token)->timeout(15)->get('https://openidconnect.googleapis.com/v1/userinfo')->throw()->json();
            if (($profile['email_verified'] ?? false) !== true || empty($profile['sub']) || ! filter_var($profile['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
                throw new \RuntimeException('Unverified identity');
            }
        } catch (\Throwable $exception) {
            return redirect()->route('login')->withErrors(['google' => 'Google sign-in could not be completed. Please try again.']);
        }
        $email = strtolower($profile['email']);
        $user = User::where('google_id', $profile['sub'])->first();
        if (! $user) {
            $user = User::where('email', $email)->first();
            // Existing password accounts must prove ownership before linking a new provider.
            if ($user && (! $user->hasVerifiedEmail() || $user->google_id)) {
                return redirect()->route('login')->withErrors(['google' => 'Sign in with your password and verify your email before using Google.']);
            }
            $user ??= User::create(['name' => $profile['name'] ?? $email, 'email' => $email, 'password' => Str::random(64)]);
            $user->forceFill(['google_id' => $profile['sub'], 'email_verified_at' => now()])->save();
        }
        $user->promoteDesignatedAdmin();
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('account'));
    }

    public function certificate(Request $request): RedirectResponse
    {
        $data = $request->validate(['mykkp_id' => 'required|string|max:100', 'certificate' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240']);
        $path = $request->file('certificate')->store('certificates', 'local');
        abort_unless($path, 500, 'Could not store certificate.');
        try {
            $old = DB::transaction(function () use ($request, $data, $path): ?string {
                $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
                $old = $user->certificate_path;
                $user->forceFill(['mykkp_id' => $data['mykkp_id'], 'certificate_path' => $path, 'certificate_status' => 'pending', 'certificate_feedback' => null, 'certificate_submitted_at' => now(), 'certificate_reviewed_at' => null, 'certificate_reviewed_by' => null])->save();

                return $old;
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }
        if ($old) {
            Storage::disk('local')->delete($old);
        }

        return back()->with('status', 'Certificate submitted for super-admin review.');
    }

    public function download(Request $request, User $user): StreamedResponse
    {
        abort_unless($request->user()->id === $user->id || $request->user()->isSuperAdmin(), 403);
        abort_unless($user->certificate_path && Storage::disk('local')->exists($user->certificate_path), 404);

        return Storage::disk('local')->download($user->certificate_path, 'mykkp-certificate.'.pathinfo($user->certificate_path, PATHINFO_EXTENSION), ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }
}
