<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isCustomer();
    }

    public function view(User $user, Order $order): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $order->user_id !== null && $order->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isCustomer();
    }

    public function cancel(User $user, Order $order): bool
    {
        return $user->isAdmin()
            || ($order->user_id === $user->id && $order->status === Order::STATUS_PENDING);
    }

    public function refund(User $user, Order $order): bool
    {
        return $user->isAdmin();
    }
}
