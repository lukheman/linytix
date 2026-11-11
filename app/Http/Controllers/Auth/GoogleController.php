<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleController extends Controller
{
    /**
     * Arahkan pengguna ke halaman persetujuan Google.
     */
    public function redirect()
    {
        if (blank(config('services.google.client_id')) || blank(config('services.google.client_secret'))) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Login Google belum dikonfigurasi.']);
        }

        return Socialite::driver('google')->redirect();
    }

    /**
     * Tangani callback OAuth Google.
     */
    public function callback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('login')
                ->withErrors(['email' => 'Login Google gagal atau dibatalkan. Silakan coba lagi.']);
        }

        $email = Str::lower(trim((string) $googleUser->getEmail()));

        if ($email === '') {
            return redirect()->route('login')
                ->withErrors(['email' => 'Akun Google tidak memiliki alamat email.']);
        }

        $user = User::query()->where('email', $email)->first();

        if ($user) {
            if (! $user->is_active) {
                return redirect()->route('login')
                    ->withErrors(['email' => 'Akun Anda sedang dinonaktifkan.']);
            }

            // Jangan hubungkan akun yang belum terverifikasi secara diam-diam.
            if (! $user->hasVerifiedEmail()) {
                return redirect()->route('login')
                    ->withErrors(['email' => 'Verifikasi email Anda terlebih dahulu sebelum masuk dengan Google.']);
            }

            if ($user->google_id && $user->google_id !== $googleUser->getId()) {
                return redirect()->route('login')
                    ->withErrors(['email' => 'Email ini sudah terhubung dengan akun Google lain.']);
            }

            $user->forceFill([
                'google_id' => $googleUser->getId(),
                'avatar' => $user->avatar ?: $googleUser->getAvatar(),
                'last_login_at' => now(),
            ])->save();
        } else {
            $user = User::create([
                'name' => $googleUser->getName() ?: Str::before($email, '@'),
                'email' => $email,
                // Akun Google tidak memakai password; simpan nilai acak agar
                // pengguna tetap dapat mengatur password lewat lupa password.
                'password' => Str::random(40),
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'is_active' => true,
            ]);

            // Email dari Google sudah terverifikasi.
            $user->markEmailAsVerified();

            $user->assignRole(Role::CUSTOMER);

            // Klaim order guest yang dibuat dengan email yang sama.
            $user->claimGuestOrders();
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        $home = route($user->homeRouteName());
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
}
