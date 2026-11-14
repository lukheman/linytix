<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EventStaffInvitation;
use App\Models\Role;
use App\Models\User;
use App\Services\Organizer\EventStaffInvitationService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    /**
     * Show the registration form.
     */
    public function show(Request $request)
    {
        $token = (string) $request->query('invitation', '');
        $invitation = $token !== '' ? EventStaffInvitation::findByToken($token) : null;
        $invitationError = null;

        if ($token !== '' && ! $invitation) {
            $invitationError = 'Undangan tidak ditemukan atau sudah tidak berlaku.';
        } elseif ($invitation && ! $invitation->isPending()) {
            $invitationError = 'Undangan ini sudah '.mb_strtolower($invitation->statusLabel()).'.';
            $invitation = null;
        } elseif ($invitation) {
            $registered = User::query()
                ->whereRaw('LOWER(email) = ?', [mb_strtolower($invitation->email)])
                ->exists();

            if ($registered) {
                return redirect()
                    ->route('login', ['redirect' => route('staff.invitations.show', $token)])
                    ->with('status', 'Email ini sudah terdaftar. Masuk untuk menerima undangan.');
            }
        }

        return view('auth.register', [
            'invitation' => $invitation,
            'invitationToken' => $invitation ? $token : null,
            'invitationError' => $invitationError,
        ]);
    }

    /**
     * Create a new user account.
     */
    public function store(Request $request)
    {
        $token = (string) $request->input('invitation', '');
        $invitation = $token !== '' ? EventStaffInvitation::findByToken($token) : null;

        if ($token !== '') {
            if (! $invitation || ! $invitation->isPending()) {
                throw ValidationException::withMessages([
                    'invitation' => 'Undangan tidak valid atau sudah tidak berlaku.',
                ]);
            }

            // Email undangan tidak boleh diganti agar penugasan tetap tepat.
            $request->merge(['email' => $invitation->email]);
        }

        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'agree_terms' => ['accepted'],
        ], [
            'agree_terms.accepted' => 'Anda harus menyetujui syarat dan ketentuan.',
        ]);

        $user = DB::transaction(function () use ($validated, $invitation, $request): User {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);

            $user->assignRole(Role::CUSTOMER);

            if ($invitation) {
                app(EventStaffInvitationService::class)
                    ->accept($invitation, $user, $request->ip());
            }

            return $user;
        });

        // Memicu pengiriman email verifikasi melalui queue.
        event(new Registered($user));

        Auth::login($user);

        $request->session()->regenerate();

        if ($invitation) {
            return redirect()->route('scan.index')->with(
                'success',
                'Akun berhasil dibuat. Anda sekarang bertugas di '
                    .$invitation->event->title.' pada gate '.$invitation->gate.'.',
            );
        }

        $organizerIntent = $request->input('intent') === 'organizer';

        return redirect()->route($user->homeRouteName())
            ->with('status', $organizerIntent
                ? 'Akun berhasil dibuat. Verifikasi email Anda, lalu lanjutkan pendaftaran organizer dari dashboard akun.'
                : 'Akun berhasil dibuat. Silakan verifikasi email Anda.');
    }
}
