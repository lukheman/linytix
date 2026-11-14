<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\User;
use App\Services\Organizer\EventStaffService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EventStaffController extends Controller
{
    /**
     * Halaman kelola staff event. Dipakai oleh admin dan organizer.
     */
    public function index(Request $request, Event $event): View
    {
        Gate::authorize('manageStaff', $event);

        $event->loadMissing(['organizer', 'venue']);

        $isAdmin = $request->routeIs('admin.*');

        return view('events.staff', [
            'event' => $event,
            'staff' => $event->staff()->orderBy('name')->get(),
            'invitations' => $event->staffInvitations()->pending()->orderBy('created_at')->get(),
            'backRoute' => $isAdmin ? 'admin.events' : 'organizer.events.index',
            'storeRoute' => $isAdmin ? 'admin.events.staff.store' : 'organizer.events.staff.store',
            'destroyRoute' => $isAdmin ? 'admin.events.staff.destroy' : 'organizer.events.staff.destroy',
            'invitationStoreRoute' => $isAdmin ? 'admin.events.staff.invitations.store' : 'organizer.events.staff.invitations.store',
            'invitationResendRoute' => $isAdmin ? 'admin.events.staff.invitations.resend' : 'organizer.events.staff.invitations.resend',
            'invitationDestroyRoute' => $isAdmin ? 'admin.events.staff.invitations.destroy' : 'organizer.events.staff.invitations.destroy',
        ]);
    }

    public function store(Request $request, Event $event, EventStaffService $staffService): RedirectResponse
    {
        Gate::authorize('manageStaff', $event);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'gate' => ['required', 'string', 'max:100'],
        ], [
            'email.required' => 'Email staff wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'gate.required' => 'Gate penugasan wajib diisi.',
        ]);

        $staff = User::query()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($validated['email']))])
            ->first();

        if (! $staff) {
            throw ValidationException::withMessages([
                'email' => 'Pengguna dengan email tersebut tidak ditemukan.',
            ]);
        }

        if (! $staff->is_active) {
            throw ValidationException::withMessages([
                'email' => 'Pengguna tersebut tidak aktif.',
            ]);
        }

        $staffService->assign($event, $staff, $validated['gate'], $request->user(), $request->ip());

        return back()->with('success', $staff->name.' berhasil ditugaskan pada gate '.$validated['gate'].'.');
    }

    public function destroy(Request $request, Event $event, User $user, EventStaffService $staffService): RedirectResponse
    {
        Gate::authorize('manageStaff', $event);

        $staffService->remove($event, $user, $request->user(), $request->ip());

        return back()->with('success', 'Penugasan staff '.$user->name.' dihapus.');
    }
}
