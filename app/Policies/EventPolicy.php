<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Event $event): bool
    {
        if ($event->isOnSale()) {
            return true;
        }

        if (! $user) {
            return false;
        }

        return $user->isAdmin() || $this->owns($user, $event) || $this->isStaff($user, $event);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isOrganizer();
    }

    public function update(User $user, Event $event): bool
    {
        return $user->isAdmin() || $this->owns($user, $event);
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->isAdmin() || ($this->owns($user, $event) && $event->status === Event::STATUS_DRAFT);
    }

    public function submitForReview(User $user, Event $event): bool
    {
        return $this->owns($user, $event)
            && $event->status === Event::STATUS_DRAFT
            && (bool) $event->organizer?->is_verified;
    }

    public function moderate(User $user): bool
    {
        return $user->isAdmin();
    }

    public function manageTickets(User $user, Event $event): bool
    {
        return $user->isAdmin() || $this->owns($user, $event);
    }

    public function scan(User $user, Event $event): bool
    {
        return $user->isAdmin() || $this->isStaff($user, $event);
    }

    private function owns(User $user, Event $event): bool
    {
        return $user->isOrganizer() && $event->organizer?->owner_user_id === $user->id;
    }

    private function isStaff(User $user, Event $event): bool
    {
        return $user->isStaff() && $user->eventAssignments()->whereKey($event->getKey())->exists();
    }
}
