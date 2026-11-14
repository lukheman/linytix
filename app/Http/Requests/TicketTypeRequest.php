<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesTicketSales;
use App\Models\Event;
use App\Models\TicketType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class TicketTypeRequest extends FormRequest
{
    use ValidatesTicketSales;

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

                $ticketType = $this->route('ticketType');

                $this->validateTicketSales(
                    $validator,
                    $event,
                    $ticketType instanceof TicketType ? $ticketType : null,
                );
            },
        ];
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
