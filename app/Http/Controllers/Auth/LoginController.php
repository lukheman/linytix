<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Batas percobaan login per email + IP.
     */
    private const MAX_ATTEMPTS = 5;

    /**
     * Show the login form.
     */
    public function show()
    {
        return view('auth.login');
    }

    /**
     * Authenticate the user.
     */
    public function store(Request $request)
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');
        $throttleKey = Str::transliterate($credentials['email'].'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => __('auth.throttle', ['seconds' => $seconds]),
            ]);
        }

        if (! Auth::attempt($credentials + ['is_active' => true], $remember)) {
            RateLimiter::hit($throttleKey, 60);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('auth.failed')]);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        $user = $request->user();
        $user->forceFill(['last_login_at' => now()])->save();

        $home = route($user->homeRouteName());
        $redirect = $this->localRedirect($request->input('redirect'));

        if ($redirect && $user->canAccessUrl($redirect)) {
            return redirect()->to($redirect);
        }

        $intended = $request->session()->pull('url.intended');

        if ($intended && $user->canAccessUrl($intended)) {
            return redirect()->to($intended);
        }

        if ($intended) {
            return redirect()->to($home)
                ->with('warning', 'Halaman yang Anda tuju tidak dapat diakses dengan peran ini. Anda diarahkan ke dashboard.');
        }

        return redirect()->to($home);
    }

    /**
     * Validasi tujuan redirect setelah login agar hanya URL lokal yang dipakai.
     */
    private function localRedirect(?string $redirect): ?string
    {
        $redirect = trim((string) $redirect);

        if ($redirect === '') {
            return null;
        }

        $parts = parse_url($redirect);

        if ($parts === false) {
            return null;
        }

        if (isset($parts['host']) && $parts['host'] !== request()->getHost()) {
            return null;
        }

        $path = $parts['path'] ?? '/';

        if (! str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return null;
        }

        return $redirect;
    }
}
