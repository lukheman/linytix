<?php

namespace App\Policies;

use App\Models\Organizer;
use App\Models\User;

class OrganizerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Organizer $organizer): bool
    {
        return $user->isAdmin() || $this->owns($user, $organizer);
    }

    public function update(User $user, Organizer $organizer): bool
    {
        return $this->owns($user, $organizer);
    }

    public function verify(User $user): bool
    {
        return $user->isAdmin();
    }

    private function owns(User $user, Organizer $organizer): bool
    {
        return $organizer->owner_user_id === $user->getKey();
    }
}
