<?php

namespace App\Http\Requests;

use App\Models\Event;
use App\Models\TicketType;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class TicketTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');

        if (! $event instanceof Event) {
            return false;
        }

        return (bool) $this->user()?->can('manageTickets', $event);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'sale_start_at' => $this->filled('sale_start_at') ? $this->input('sale_start_at') : null,
            'sale_end_at' => $this->filled('sale_end_at') ? $this->input('sale_end_at') : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $ticketType = $this->route('ticketType');
        $minQuota = $ticketType instanceof TicketType ? max(1, $ticketType->sold_count) : 1;

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'integer', 'min:0'],
            'quota' => ['required', 'integer', 'min:'.$minQuota],
            'min_per_order' => ['required', 'integer', 'min:1', 'max:10'],
            'max_per_order' => ['required', 'integer', 'min:1', 'max:10', 'gte:min_per_order'],
            'sale_start_at' => ['nullable', 'date'],
            'sale_end_at' => ['nullable', 'date'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $event = $this->route('event');

                if (! $event instanceof Event) {
                    return;
                }

                $this->validateSaleWindow($validator, $event);
                $this->validateCapacity($validator, $event);
            },
        ];
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
    private function validateCapacity(Validator $validator, Event $event): void
    {
        if ($validator->errors()->has('quota')) {
            return;
        }

        $ticketType = $this->route('ticketType');
        $excludeId = $ticketType instanceof TicketType ? $ticketType->getKey() : null;
        $projectedTotal = $event->ticketQuotaTotal($excludeId) + (int) $this->input('quota', 0);
        $overflow = $event->capacityOverflowFor($projectedTotal);

        if ($overflow) {
            $validator->errors()->add(
                'quota',
                'Total kuota tiket ('.$overflow['total'].') melebihi kapasitas venue ('.$overflow['capacity'].').'
            );
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama jenis tiket wajib diisi.',
            'price.required' => 'Harga tiket wajib diisi.',
            'price.min' => 'Harga tiket tidak boleh negatif.',
            'quota.required' => 'Kuota tiket wajib diisi.',
            'quota.min' => 'Kuota tidak boleh lebih kecil dari jumlah tiket terjual.',
            'max_per_order.gte' => 'Maksimal tiket per order harus lebih besar atau sama dengan minimal per order.',
        ];
    }
}
