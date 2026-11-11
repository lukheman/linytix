<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organizer;
use App\Services\Organizer\OrganizerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OrganizerController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Organizer::class);

        $query = Organizer::query()
            ->with('owner')
            ->withCount('events')
            ->latest();

        $search = trim((string) $request->query('q', ''));

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', '%'.$search.'%')
                    ->orWhere('slug', 'like', '%'.$search.'%')
                    ->orWhere('contact_email', 'like', '%'.$search.'%')
                    ->orWhereHas('owner', function ($owner) use ($search): void {
                        $owner->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%');
                    });
            });
        }

        $status = (string) $request->query('status', '');

        if ($status === 'verified') {
            $query->where('is_verified', true);
        } elseif ($status === 'unverified') {
            $query->where('is_verified', false);
        }

        $organizers = $query->paginate(20)->withQueryString();

        return view('admin.organizers', [
            'organizers' => $organizers,
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function verify(
        Request $request,
        Organizer $organizer,
        OrganizerService $service,
    ): RedirectResponse {
        Gate::authorize('verify', Organizer::class);

        $validated = $request->validate([
            'action' => ['required', 'in:verify,unverify'],
            'verification_note' => ['nullable', 'string', 'max:1000'],
        ], [
            'action.required' => 'Aksi verifikasi tidak valid.',
            'action.in' => 'Aksi verifikasi tidak valid.',
        ]);

        $verified = $validated['action'] === 'verify';

        $service->verify(
            $organizer,
            $request->user(),
            $verified,
            $validated['verification_note'] ?? null,
            $request->ip(),
        );

        return back()->with(
            'success',
            $verified
                ? 'Organizer '.$organizer->name.' berhasil diverifikasi.'
                : 'Verifikasi organizer '.$organizer->name.' dibatalkan.',
        );
    }
}
