<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($ticket->order?->user_id === $user->id) {
            return true;
        }

        return $user->isStaff()
            && $user->eventAssignments()->whereKey($ticket->event_id)->exists();
    }

    public function download(User $user, Ticket $ticket): bool
    {
        return $ticket->status === Ticket::STATUS_ACTIVE && $this->view($user, $ticket);
    }

    public function scan(User $user): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }
}
