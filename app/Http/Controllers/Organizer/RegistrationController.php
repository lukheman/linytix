<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterOrganizerRequest;
use App\Services\Organizer\OrganizerService;
use App\Support\SiteContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->isOrganizer() || $user->organizers()->exists()) {
            return redirect()
                ->route('organizer.dashboard')
                ->with('status', 'Anda sudah terdaftar sebagai organizer.');
        }

        return view('organizer.register', [
            'user' => $user,
            'settings' => SiteContent::settings(),
        ]);
    }

    public function store(
        RegisterOrganizerRequest $request,
        OrganizerService $service,
    ): RedirectResponse {
        $user = $request->user();

        if ($user->isOrganizer() || $user->organizers()->exists()) {
            return redirect()
                ->route('organizer.dashboard')
                ->with('status', 'Anda sudah terdaftar sebagai organizer.');
        }

        $service->register($user, $request->validated(), $request->ip());

        return redirect()
            ->route('organizer.dashboard')
            ->with('status', 'Pengajuan organizer terkirim. Menunggu verifikasi admin.');
    }
}
