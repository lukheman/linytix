<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCheckoutRequest;
use App\Jobs\ExpireOrders;
use App\Models\Event;
use App\Models\Order;
use App\Models\Payment;
use App\Models\TicketType;
use App\Services\Checkout\OrderService;
use App\Services\Payment\PaymentManager;
use App\Services\Payment\PaymentReconciliationService;
use App\Support\GuestOrderAccess;
use App\Support\SiteContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly PaymentManager $payments,
    ) {}

    /**
     * Form pemilihan tiket dan data pembeli.
     */
    public function create(Request $request, Event $event): View|Response
    {
        if (! $event->isOnSale() || $event->start_at?->isPast()) {
            return response()->view('guest.checkout.unavailable', [
                'settings' => SiteContent::settings(),
            ], 404);
        }

        $ticketTypes = $event->ticketTypes()
            ->where('is_active', true)
            ->orderBy('price')
            ->get();

        if ($ticketTypes->isEmpty()) {
            return response()->view('guest.checkout.unavailable', [
                'settings' => SiteContent::settings(),
            ], 404);
        }

        $selectedId = (string) $request->query('ticket_type', '');
        $selected = $ticketTypes->firstWhere('id', $selectedId);

        return view('guest.checkout.create', [
            'event' => $event->load(['venue', 'organizer', 'category']),
            'ticketTypes' => $ticketTypes,
            'selectedTicketTypeId' => $selected?->getKey() ?? $ticketTypes->firstWhere(fn (TicketType $type) => $type->isOnSaleNow())?->getKey(),
            'idempotencyKey' => (string) str()->uuid(),
            'settings' => SiteContent::settings(),
        ]);
    }

    /**
     * Buat order pending lalu arahkan ke halaman status pembayaran.
     */
    public function store(StoreCheckoutRequest $request, Event $event): RedirectResponse
    {
        $ticketType = TicketType::query()
            ->where('event_id', $event->getKey())
            ->whereKey($request->string('ticket_type_id')->toString())
            ->firstOrFail();

        $order = $this->orders->create(
            event: $event,
            ticketType: $ticketType,
            quantity: (int) $request->input('quantity'),
            buyer: [
                'name' => (string) $request->input('buyer_name'),
                'email' => (string) $request->input('buyer_email'),
                'phone' => (string) $request->input('buyer_phone'),
            ],
            holderNames: (array) $request->input('holder_names', []),
            idempotencyKey: $request->filled('idempotency_key') ? (string) $request->input('idempotency_key') : null,
            user: $request->user(),
            expectedUnitPrice: $request->filled('expected_unit_price') ? (int) $request->input('expected_unit_price') : null,
        );

        $url = $request->user() && $order->user_id === $request->user()->getKey()
            ? route('checkout.show', $order)
            : GuestOrderAccess::checkoutUrlFor($order);

        return redirect($url)->with('status', 'Pesanan berhasil dibuat. Selesaikan pembayaran sebelum batas waktu.');
    }

    /**
     * Halaman status dan instruksi pembayaran.
     */
    public function show(Request $request, Order $order): View|Response
    {
        $this->authorizeAccess($request, $order);

        $this->syncPendingPayment($order);
        $this->expireIfPastDeadline($order);

        $order->load([
            'event.venue',
            'items.ticketType',
            'payments' => fn ($query) => $query->latest(),
            'tickets.ticketType',
        ]);

        $signed = $request->hasValidSignature();
        $latestPayment = $order->payments->first();

        return view('guest.checkout.show', [
            'order' => $order,
            'settings' => SiteContent::settings(),
            'paymentMethods' => $this->availableMethods(),
            'latestPayment' => $latestPayment,
            'expired' => $order->isPending() && $order->isExpired(),
            'payUrl' => $signed
                ? GuestOrderAccess::paymentUrlFor($order)
                : route('checkout.pay', $order),
            'ticketsUrl' => $signed
                ? GuestOrderAccess::urlFor($order)
                : route('akun.orders.show', $order),
            'pdfUrl' => $order->tickets->isNotEmpty()
                ? ($signed ? GuestOrderAccess::pdfUrlFor($order) : route('akun.orders.pdf', $order))
                : null,
            'statusUrl' => $signed
                ? GuestOrderAccess::statusUrlFor($order)
                : route('checkout.status', $order),
            'signed' => $signed,
        ]);
    }

    /**
     * Status ringkas untuk polling halaman pembayaran.
     */
    public function status(Request $request, Order $order): JsonResponse
    {
        $this->authorizeAccess($request, $order);

        $this->syncPendingPayment($order);
        $this->expireIfPastDeadline($order);

        $order->load(['payments' => fn ($query) => $query->latest()]);

        return response()
            ->json([
                'status' => $order->status,
                'expired' => $order->isPending() && $order->isExpired(),
                'payment_status' => $order->payments->first()?->status,
                'has_tickets' => $order->tickets()->exists(),
            ])
            ->header('Cache-Control', 'no-store, max-age=0');
    }

    /**
     * Inisiasi pembayaran ke gateway.
     */
    public function pay(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeAccess($request, $order);

        $this->syncPendingPayment($order);
        $this->expireIfPastDeadline($order);

        $availableMethods = array_keys($this->availableMethods());

        $validated = $request->validate([
            'method' => ['required', 'string', 'in:'.implode(',', $availableMethods)],
        ]);

        if (! $order->canBePaid()) {
            return back()->withErrors(['payment' => 'Pesanan ini sudah tidak dapat dibayar.']);
        }

        $method = (string) $validated['method'];

        $existing = $order->payments()
            ->where('status', Payment::STATUS_PENDING)
            ->where('method', $method)
            ->latest()
            ->first();

        if ($existing && $existing->payment_url && ($existing->expires_at === null || $existing->expires_at->isFuture())) {
            return redirect()->away($existing->payment_url);
        }

        $gateway = $this->payments->gateway();

        try {
            $charge = $gateway->createCharge($order, $method);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'payment' => 'Gagal memulai pembayaran. Pesanan tetap tersimpan, silakan coba lagi.',
            ]);
        }

        $expiresAt = $order->expires_at;

        if (! empty($charge['expires_at'])) {
            $gatewayExpiresAt = Carbon::parse($charge['expires_at']);
            $expiresAt = $expiresAt && $expiresAt->lt($gatewayExpiresAt) ? $expiresAt : $gatewayExpiresAt;
        }

        $payment = $order->payments()->create([
            'gateway' => $gateway->name(),
            'method' => $method,
            'gateway_ref' => $charge['gateway_ref'] ?? null,
            'amount' => $order->total_amount,
            'status' => Payment::STATUS_PENDING,
            'payment_url' => $charge['payment_url'] ?? null,
            'raw_response' => $charge['raw'] ?? [],
            'expires_at' => $expiresAt,
        ]);

        if (filled($payment->payment_url)) {
            return redirect()->away($payment->payment_url);
        }

        return back()->with('status', 'Instruksi pembayaran berhasil dibuat.');
    }

    /**
     * Sinkronkan pembayaran pending dengan gateway saat halaman status dibuka
     * atau dipolling. Ini menjadi fallback bila webhook tidak sampai atau
     * worker/scheduler belum berjalan.
     */
    private function syncPendingPayment(Order $order): void
    {
        if (! $order->isPending()) {
            return;
        }

        $payment = $order->payments()->latest()->first();

        if (! $payment || $payment->status !== Payment::STATUS_PENDING || blank($payment->gateway_ref)) {
            return;
        }

        // Batasi panggilan gateway agar polling 5 detik tidak membanjiri API.
        if (! Cache::add('payment-reconcile:'.$payment->getKey(), true, now()->addSeconds(30))) {
            return;
        }

        try {
            app(PaymentReconciliationService::class)->reconcile($payment);
        } catch (Throwable $exception) {
            report($exception);
        }

        $order->refresh();
    }

    /**
     * Kedaluwarsakan order yang sudah melewati batas waktu tanpa menunggu
     * job terjadwal berjalan.
     */
    private function expireIfPastDeadline(Order $order): void
    {
        if (! $order->isPending() || ! $order->isExpired()) {
            return;
        }

        app(ExpireOrders::class)->expireOrder($order);

        $order->refresh();
    }

    /**
     * Metode pembayaran yang didukung gateway aktif dan dikonfigurasi aplikasi.
     *
     * @return array<string, string>
     */
    private function availableMethods(): array
    {
        $configured = (array) config('ticketing.payment_methods', []);
        $supported = array_flip($this->payments->gateway()->supportedMethods());

        $available = array_intersect_key($configured, $supported);

        return $available !== [] ? $available : $configured;
    }

    private function authorizeAccess(Request $request, Order $order): void
    {
        if ($request->hasValidSignature()) {
            return;
        }

        $user = $request->user();

        abort_unless(
            $user && ($user->isAdmin() || $order->user_id === $user->getKey()),
            403,
        );
    }
}
