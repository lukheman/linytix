<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Ticket;
use App\Support\SiteContent;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return view('account.dashboard', [
            'user' => $user,
            'orderCount' => $user->orders()->count(),
            'ticketCount' => Ticket::query()
                ->whereHas('order', fn ($query) => $query->where('user_id', $user->id)->where('status', Order::STATUS_PAID))
                ->count(),
            'settings' => SiteContent::settings(),
            'upcoming' => $user->orders()
                ->paid()
                ->whereHas('event', fn ($query) => $query->where('start_at', '>=', now()))
                ->with('event')
                ->orderBy('paid_at')
                ->limit(3)
                ->get(),
        ]);
    }
}
