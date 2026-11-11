<?php

namespace App\Policies;

use App\Models\Refund;
use App\Models\User;

class RefundPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Refund $refund): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function process(User $user, Refund $refund): bool
    {
        return $user->isAdmin()
            && in_array($refund->status, [Refund::STATUS_REQUESTED, Refund::STATUS_FAILED], true);
    }
}
