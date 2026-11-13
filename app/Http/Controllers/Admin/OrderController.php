<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendTicketEmail;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\NotificationLog;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\PaymentReconciliationService;
use App\Support\TicketPdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderController extends Controller
{
    /**
     * Daftar pesanan beserta status email e-tiket.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Order::class);

        $query = Order::query()
            ->with(['event', 'refunds'])
            ->withCount('tickets')
            ->latest();

        $search = $request->query('q');
        $search = is_string($search) ? trim($search) : '';

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('order_number', 'like', '%'.$search.'%')
                    ->orWhere('buyer_email', 'like', '%'.$search.'%')
                    ->orWhere('buyer_name', 'like', '%'.$search.'%');
            });
        }

        $status = $request->query('status');
        $status = is_string($status) ? $status : '';

        if (in_array($status, [
            Order::STATUS_PENDING,
            Order::STATUS_PAID,
            Order::STATUS_EXPIRED,
            Order::STATUS_CANCELLED,
            Order::STATUS_REFUNDED,
        ], true)) {
            $query->where('status', $status);
        } else {
            $status = '';
        }

        $eventId = $request->query('event_id');
        $eventId = is_string($eventId) ? $eventId : '';

        if (Str::isUuid($eventId)) {
            $query->where('event_id', $eventId);
        } else {
            $eventId = '';
        }

        $dateFromInput = $request->query('date_from');
        $dateFromInput = is_string($dateFromInput) ? trim($dateFromInput) : '';
        $dateToInput = $request->query('date_to');
        $dateToInput = is_string($dateToInput) ? trim($dateToInput) : '';
        $dateFrom = $this->parseDate($dateFromInput);
        $dateTo = $this->parseDate($dateToInput);

        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom->startOfDay());
        }

        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo->endOfDay());
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
            'events' => Event::query()
                ->whereHas('orders')
                ->orderBy('title')
                ->get(['id', 'title']),
            'search' => $search,
            'status' => $status,
            'eventId' => $eventId,
            'dateFrom' => $dateFromInput,
            'dateTo' => $dateToInput,
        ]);
    }

    /**
     * Detail pesanan: item, pembayaran, tiket, dan riwayat refund.
     */
    public function show(Order $order): View
    {
        Gate::authorize('view', $order);

        $order->load([
            'user',
            'event.organizer',
            'event.venue',
            'items.ticketType',
            'payments',
            'tickets.ticketType',
            'tickets.checkIns',
            'refunds.payment',
            'refunds.requester',
            'refunds.processor',
            'notificationLogs',
        ]);

        return view('admin.order-detail', [
            'order' => $order,
        ]);
    }

    /**
     * Kirim ulang email e-tiket untuk pesanan yang sudah dibayar.
     */
    public function resendTicket(Request $request, Order $order): RedirectResponse
    {
        Gate::authorize('view', $order);

        if ($order->status !== Order::STATUS_PAID || ! $order->tickets()->exists()) {
            return back()->with('error', 'E-tiket belum tersedia untuk pesanan ini.');
        }

        SendTicketEmail::dispatch($order->getKey());

        AuditLog::query()->create([
            'user_id' => $request->user()->getKey(),
            'action' => 'order.ticket_resent',
            'entity' => 'order',
            'entity_id' => $order->getKey(),
            'meta' => [
                'order_number' => $order->order_number,
            ],
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Email e-tiket untuk '.$order->order_number.' dijadwalkan ulang.');
    }

    /**
     * Rekonsiliasi manual satu pembayaran gagal/expired/pending.
     */
    public function reconcilePayment(
        Request $request,
        Order $order,
        Payment $payment,
        PaymentReconciliationService $service,
    ): RedirectResponse {
        Gate::authorize('refund', $order);

        abort_unless($payment->order_id === $order->getKey(), 404);

        try {
            $result = $service->reconcile($payment, $request->user(), $request->ip());
        } catch (ValidationException $exception) {
            return back()
                ->withErrors($exception->errors())
                ->with('error', collect($exception->errors())->flatten()->first());
        }

        $flash = $result['result'] === PaymentReconciliationService::RESULT_ERROR
            ? 'error'
            : 'success';

        return back()->with($flash, $result['message']);
    }

    public function pdf(Order $order)
    {
        Gate::authorize('view', $order);

        abort_if($order->tickets()->doesntExist(), 404);

        return TicketPdf::download($order);
    }

    private function parseDate(string $value): ?Carbon
    {
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
