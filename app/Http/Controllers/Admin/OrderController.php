<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendTicketEmail;
use App\Models\NotificationLog;
use App\Models\Order;
use App\Support\TicketPdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    /**
     * Daftar pesanan beserta status email e-tiket.
     */
    public function index(Request $request): View
    {
        $query = Order::query()
            ->with(['event', 'refunds'])
            ->withCount('tickets')
            ->latest();

        $search = trim((string) $request->query('q', ''));

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('order_number', 'like', '%'.$search.'%')
                    ->orWhere('buyer_email', 'like', '%'.$search.'%')
                    ->orWhere('buyer_name', 'like', '%'.$search.'%');
            });
        }

        $status = (string) $request->query('status', '');

        if (in_array($status, [
            Order::STATUS_PENDING,
            Order::STATUS_PAID,
            Order::STATUS_EXPIRED,
            Order::STATUS_CANCELLED,
            Order::STATUS_REFUNDED,
        ], true)) {
            $query->where('status', $status);
        }

        $orders = $query->paginate(20)->withQueryString();

        $notificationLogs = NotificationLog::query()
            ->whereIn('order_id', $orders->pluck('id'))
            ->where('template', 'ticket_issued')
            ->latest()
            ->get()
            ->groupBy('order_id')
            ->map(fn ($logs) => $logs->first());

        return view('admin.orders', [
            'orders' => $orders,
            'notificationLogs' => $notificationLogs,
            'search' => $search,
            'status' => $status,
        ]);
    }

    /**
     * Kirim ulang email e-tiket untuk pesanan yang sudah dibayar.
     */
    public function resendTicket(Order $order): RedirectResponse
    {
        if ($order->status !== Order::STATUS_PAID || ! $order->tickets()->exists()) {
            return back()->with('error', 'E-tiket belum tersedia untuk pesanan ini.');
        }

        SendTicketEmail::dispatch($order->getKey());

        return back()->with('success', 'Email e-tiket untuk '.$order->order_number.' dijadwalkan ulang.');
    }

    public function pdf(Order $order)
    {
        abort_if($order->tickets()->doesntExist(), 404);

        return TicketPdf::download($order);
    }
}
