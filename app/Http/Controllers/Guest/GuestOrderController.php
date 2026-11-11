<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Notifications\GuestOrderLink;
use App\Support\GuestOrderAccess;
use App\Support\SiteContent;
use App\Support\TicketPdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class GuestOrderController extends Controller
{
    private const LOOKUP_ATTEMPTS = 10;

    public function lookupForm(): View
    {
        return view('guest.orders.lookup', ['settings' => SiteContent::settings()]);
    }

    /**
     * Cari order dengan nomor + email, lalu kirim signed link ke email.
     * Respons selalu generik agar order tidak dapat dienumerasi.
     */
    public function lookup(Request $request)
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
            'order_number' => trim((string) $request->input('order_number')),
        ]);

        $validated = $request->validate([
            'order_number' => ['required', 'string', 'max:64'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $throttleKey = 'guest-order-lookup|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::LOOKUP_ATTEMPTS)) {
            return back()
                ->withInput($request->only('order_number', 'email'))
                ->withErrors(['order_number' => 'Terlalu banyak percobaan. Coba lagi dalam satu menit.']);
        }

        RateLimiter::hit($throttleKey, 60);

        $order = Order::query()
            ->with('event')
            ->where('order_number', $validated['order_number'])
            ->whereRaw('LOWER(buyer_email) = ?', [$validated['email']])
            ->first();

        if ($order) {
            Notification::route('mail', $order->buyer_email)
                ->notify(new GuestOrderLink($order, GuestOrderAccess::urlFor($order)));
        }

        return back()->with(
            'status',
            'Jika data cocok dengan pesanan kami, tautan akses telah dikirim ke email pembeli.',
        );
    }

    /**
     * Detail pesanan guest melalui signed URL.
     */
    public function show(Request $request, Order $order): View|Response
    {
        if (! $request->hasValidSignature()) {
            return response()->view('guest.orders.invalid', [
                'settings' => SiteContent::settings(),
            ], 403);
        }

        $order->load([
            'event.venue',
            'items.ticketType',
            'payments' => fn ($query) => $query->latest(),
            'tickets.ticketType',
        ]);

        return view('guest.orders.show', [
            'order' => $order,
            'settings' => SiteContent::settings(),
            'accessExpiresAt' => GuestOrderAccess::expiresAt($order),
            'pdfUrl' => $order->tickets->isNotEmpty() ? GuestOrderAccess::pdfUrlFor($order) : null,
        ]);
    }

    public function pdf(Request $request, Order $order)
    {
        abort_unless($request->hasValidSignature(), 403);

        $order->loadMissing(['event.venue', 'tickets.ticketType']);

        abort_if($order->tickets->isEmpty(), 404);

        return TicketPdf::download($order);
    }
}
