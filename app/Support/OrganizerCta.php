<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

class OrganizerCta
{
    /**
     * Tujuan CTA organizer.
     *
     * Guest diarahkan ke registrasi akun dengan konteks organizer, sedangkan
     * user yang sudah masuk diarahkan ke halaman pengajuan profil organizer.
     */
    public static function url(): string
    {
        return Auth::check()
            ? route('organizer.register')
            : route('register', ['intent' => 'organizer']);
    }
}
