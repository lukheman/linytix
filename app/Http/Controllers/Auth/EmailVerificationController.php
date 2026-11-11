<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailVerificationController extends Controller
{
    /**
     * Halaman pemberitahuan verifikasi email.
     */
    public function notice(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('akun.dashboard');
        }

        return view('auth.verify-email');
    }

    /**
     * Tandai email terverifikasi melalui signed URL.
     */
    public function verify(Request $request, string $id, string $hash)
    {
        $user = User::query()->findOrFail($id);

        abort_unless(hash_equals(sha1($user->getEmailForVerification()), $hash), 403);

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();

            // Order guest dengan email yang sama otomatis diklaim.
            $user->claimGuestOrders();
        }

        if (Auth::check() && Auth::id() === $user->id) {
            return redirect()->route($user->homeRouteName())
                ->with('status', 'Email berhasil diverifikasi.');
        }

        return redirect()->route('login')
            ->with('status', 'Email berhasil diverifikasi. Silakan masuk.');
    }

    /**
     * Kirim ulang email verifikasi.
     */
    public function send(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return back()->with('status', 'Email Anda sudah terverifikasi.');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'Tautan verifikasi baru telah dikirim ke email Anda.');
    }
}
