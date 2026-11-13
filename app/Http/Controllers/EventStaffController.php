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

        return view('events.staff', [
            'event' => $event,
            'staff' => $event->staff()->orderBy('name')->get(),
            'backRoute' => $request->routeIs('admin.*') ? 'admin.events' : 'organizer.events.index',
            'storeRoute' => $request->routeIs('admin.*') ? 'admin.events.staff.store' : 'organizer.events.staff.store',
            'destroyRoute' => $request->routeIs('admin.*') ? 'admin.events.staff.destroy' : 'organizer.events.staff.destroy',
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
