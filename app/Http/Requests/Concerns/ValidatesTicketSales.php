<?php

namespace App\Http\Requests\Concerns;

use App\Models\Event;
use App\Models\TicketType;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;

trait ValidatesTicketSales
{
    /**
     * Validasi jadwal jual dan kapasitas venue untuk satu jenis tiket.
     */
    protected function validateTicketSales(
        Validator $validator,
        Event $event,
        ?TicketType $ticketType = null,
    ): void {
        $this->validateSaleWindow($validator, $event);
        $this->validateCapacity($validator, $event, $ticketType);
    }

    /**
     * Jadwal jual harus berakhir sebelum event dimulai dan start < end.
     */
    private function validateSaleWindow(Validator $validator, Event $event): void
    {
        if ($validator->errors()->has('sale_start_at') || $validator->errors()->has('sale_end_at')) {
            return;
        }

        $timezone = $event->timezoneLabel();
        $saleStart = $this->filled('sale_start_at')
            ? Carbon::parse((string) $this->input('sale_start_at'), $timezone)
            : null;
        $saleEnd = $this->filled('sale_end_at')
            ? Carbon::parse((string) $this->input('sale_end_at'), $timezone)
            : null;
        $eventStart = $event->startsAtLocal();

        if ($saleStart && $saleEnd && $saleEnd->lte($saleStart)) {
            $validator->errors()->add('sale_end_at', 'Akhir penjualan harus setelah awal penjualan.');

            return;
        }

        if (! $eventStart) {
            return;
        }

        if ($saleEnd && $saleEnd->gt($eventStart)) {
            $validator->errors()->add('sale_end_at', 'Penjualan tiket harus berakhir paling lambat saat event dimulai.');
        } elseif (! $saleEnd && $saleStart && $saleStart->gte($eventStart)) {
            $validator->errors()->add('sale_start_at', 'Penjualan tiket harus dimulai sebelum event dimulai.');
        }
    }

    /**
     * Total kuota semua jenis tiket tidak boleh melebihi kapasitas venue.
     */
    private function validateCapacity(
        Validator $validator,
        Event $event,
        ?TicketType $ticketType = null,
    ): void {
        if ($validator->errors()->has('quota')) {
            return;
        }

        $projectedTotal = $event->ticketQuotaTotal($ticketType?->getKey()) + (int) $this->input('quota', 0);
        $overflow = $event->capacityOverflowFor($projectedTotal);

        if ($overflow) {
            $validator->errors()->add(
                'quota',
                'Total kuota tiket ('.$overflow['total'].') melebihi kapasitas venue ('.$overflow['capacity'].').'
            );
        }
    }
}
