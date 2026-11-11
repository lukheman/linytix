<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Order;
use App\Models\Ticket;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'stats' => [
                'events' => Event::query()->count(),
                'published_events' => Event::query()->published()->count(),
                'orders' => Order::query()->count(),
                'tickets' => Ticket::query()->count(),
            ],
            'recentOrders' => Order::query()->with('event')->latest()->limit(5)->get(),
        ]);
    }
}
