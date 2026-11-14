<?php

namespace App\Http\Controllers;

use App\Http\Requests\StaffInvitationRequest;
use App\Models\Event;
use App\Models\EventStaffInvitation;
use App\Models\User;
use App\Services\Organizer\EventStaffInvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StaffInvitationController extends Controller
{
    /**
     * Kirim undangan staff atau langsung tugaskan akun yang sudah ada.
     */
    public function store(
        StaffInvitationRequest $request,
        Event $event,
        EventStaffInvitationService $invitations,
    ): RedirectResponse {
        Gate::authorize('manageStaff', $event);

        $result = $invitations->invite(
            $event,
            (string) $request->validated('email'),
            (string) $request->validated('gate'),
            $request->user(),
            $request->ip(),
        );

        if ($result['type'] === 'assigned') {
            return back()->with(
                'success',
                $result['user']->name.' sudah memiliki akun dan langsung ditugaskan pada gate '
                    .$request->validated('gate').'.',
            );
        }

        return back()->with(
            'success',
            'Undangan staff telah dikirim ke '.$request->validated('email').'.',
        );
    }

    /**
     * Kirim ulang undangan dengan token baru.
     */
    public function resend(
        Request $request,
        Event $event,
        EventStaffInvitation $invitation,
        EventStaffInvitationService $invitations,
    ): RedirectResponse {
        Gate::authorize('manageStaff', $event);

        abort_unless($invitation->event_id === $event->getKey(), 404);

        $invitations->resend($invitation, $request->user(), $request->ip());

        return back()->with('success', 'Undangan dikirim ulang ke '.$invitation->email.'.');
    }

    /**
     * Batalkan undangan yang masih menunggu.
     */
    public function destroy(
        Request $request,
        Event $event,
        EventStaffInvitation $invitation,
        EventStaffInvitationService $invitations,
    ): RedirectResponse {
        Gate::authorize('manageStaff', $event);

        abort_unless($invitation->event_id === $event->getKey(), 404);

        $invitations->revoke($invitation, $request->user(), $request->ip());

        return back()->with('success', 'Undangan untuk '.$invitation->email.' dibatalkan.');
    }

    /**
     * Halaman publik untuk membuka undangan.
     */
    public function show(Request $request, string $token): View
    {
        $invitation = EventStaffInvitation::findByToken($token);

        abort_if(! $invitation, 404);

        $invitation->loadMissing(['event.organizer', 'event.venue']);

        $user = $request->user();
        $registeredUser = User::query()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($invitation->email)])
            ->first();

        return view('staff.invitations.show', [
            'invitation' => $invitation,
            'event' => $invitation->event,
            'registeredUser' => $registeredUser,
            'isRecipient' => $user
                && mb_strtolower($user->email) === mb_strtolower($invitation->email),
            'token' => $token,
            'acceptRoute' => route('staff.invitations.accept', $token),
            'registerUrl' => route('register', ['invitation' => $token]),
            'loginUrl' => route('login', [
                'redirect' => route('staff.invitations.show', $token),
            ]),
        ]);
    }

    /**
     * Terima undangan sebagai pengguna yang sudah masuk.
     */
    public function accept(
        Request $request,
        string $token,
        EventStaffInvitationService $invitations,
    ): RedirectResponse {
        $invitation = EventStaffInvitation::findByToken($token);

        abort_if(! $invitation, 404);

        $invitations->accept($invitation, $request->user(), $request->ip());

        return redirect()->route('scan.index')->with(
            'success',
            'Undangan diterima. Anda sekarang bertugas di '.$invitation->event->title.'.',
        );
    }
}
