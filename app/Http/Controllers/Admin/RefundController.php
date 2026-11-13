<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Order;
use App\Models\Refund;
use App\Services\Refund\RefundService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RefundController extends Controller
{
    /**
     * Daftar refund beserta filter status dan aksi retry.
     */
    public function index(Request $request): View
    {
        $query = Refund::query()
            ->with(['order.event', 'payment', 'requester', 'processor'])
            ->latest();

        $search = trim((string) $request->query('q', ''));

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->whereHas('order', function ($order) use ($search): void {
                    $order->where('order_number', 'like', '%'.$search.'%')
                        ->orWhere('buyer_email', 'like', '%'.$search.'%')
                        ->orWhere('buyer_name', 'like', '%'.$search.'%');
                });
            });
        }

        $status = (string) $request->query('status', '');

        if (in_array($status, [
            Refund::STATUS_REQUESTED,
            Refund::STATUS_PROCESSING,
            Refund::STATUS_SUCCEEDED,
            Refund::STATUS_FAILED,
        ], true)) {
            $query->where('status', $status);
        }

        $refunds = $query->paginate(20)->withQueryString();

        $counts = Refund::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $cancellableEvents = Event::query()
            ->whereIn('status', [
                Event::STATUS_PUBLISHED,
                Event::STATUS_SOLD_OUT,
            ])
            ->orderBy('title')
            ->get(['id', 'title', 'status', 'start_at']);

        return view('admin.refunds', [
            'refunds' => $refunds,
            'counts' => $counts,
            'cancellableEvents' => $cancellableEvents,
            'search' => $search,
            'status' => $status,
        ]);
    }

    /**
     * Ajukan refund penuh untuk pesanan yang sudah lunas.
     */
    public function store(Request $request, Order $order, RefundService $service): RedirectResponse
    {
        Gate::authorize('create', Refund::class);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'reason.required' => 'Alasan refund wajib diisi.',
            'reason.min' => 'Alasan refund minimal 5 karakter.',
        ]);

        try {
            $refund = $service->requestFullRefund(
                $order,
                $request->user(),
                $validated['reason'],
                $request->ip(),
            );
        } catch (ValidationException $exception) {
            return back()
                ->withErrors($exception->errors())
                ->with('error', collect($exception->errors())->flatten()->first());
        }

        $message = $refund->wasRecentlyCreated
            ? 'Refund untuk '.$order->order_number.' sedang diproses.'
            : 'Refund untuk '.$order->order_number.' sudah pernah diajukan (status: '.$refund->status.').';

        return back()->with('success', $message);
    }

    /**
     * Proses ulang refund yang gagal.
     */
    public function retry(Request $request, Refund $refund, RefundService $service): RedirectResponse
    {
        Gate::authorize('process', $refund);

        try {
            $service->retry($refund, $request->user(), $request->ip());
        } catch (ValidationException $exception) {
            return back()
                ->withErrors($exception->errors())
                ->with('error', collect($exception->errors())->flatten()->first());
        }

        return back()->with('success', 'Refund untuk pesanan '.($refund->order?->order_number ?? $refund->getKey()).' dijadwalkan ulang.');
    }

    /**
     * Batalkan event dan ajukan refund untuk seluruh pesanan lunas.
     */
    public function cancelEvent(Request $request, RefundService $service): RedirectResponse
    {
        Gate::authorize('moderate', Event::class);

        $validated = $request->validate([
            'event_id' => ['required', 'uuid', 'exists:events,id'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'event_id.required' => 'Event wajib dipilih.',
            'event_id.exists' => 'Event tidak ditemukan.',
            'reason.required' => 'Alasan pembatalan event wajib diisi.',
            'reason.min' => 'Alasan pembatalan minimal 5 karakter.',
        ]);

        $event = Event::query()->findOrFail($validated['event_id']);

        $result = $service->cancelEvent(
            $event,
            $request->user(),
            $validated['reason'],
            $request->ip(),
        );

        return back()->with(
            'success',
            sprintf(
                'Event "%s" dibatalkan. %d pesanan lunas diproses: %d refund baru, %d sudah ada, %d gagal diajukan.',
                $event->title,
                $result['orders'],
                $result['created'],
                $result['existing'],
                $result['failed'],
            ),
        );
    }
}
