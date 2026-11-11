<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOrganizerProfileRequest;
use App\Services\Organizer\OrganizerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request, OrganizerService $service): View
    {
        $organizer = $service->forUser($request->user());

        Gate::authorize('update', $organizer);

        return view('organizer.profile', [
            'organizer' => $organizer,
        ]);
    }

    public function update(
        UpdateOrganizerProfileRequest $request,
        OrganizerService $service,
    ): RedirectResponse {
        $organizer = $service->forUser($request->user());

        Gate::authorize('update', $organizer);

        $oldSlug = $organizer->slug;

        $organizer = $service->update(
            $organizer,
            $request->user(),
            $request->validated(),
            $request->ip(),
        );

        $message = 'Profil organizer berhasil diperbarui.';

        if ($organizer->slug !== $oldSlug) {
            $message .= ' URL organizer lama otomatis dialihkan ke slug baru.';
        }

        return back()->with('success', $message);
    }
}
