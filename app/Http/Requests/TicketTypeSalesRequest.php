<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesTicketSales;
use App\Models\Event;
use App\Models\TicketType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class TicketTypeSalesRequest extends FormRequest
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
            'quota' => ['required', 'integer', 'min:'.$minQuota],
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
            'quota.required' => 'Kuota tiket wajib diisi.',
            'quota.min' => 'Kuota minimal 1 dan tidak boleh lebih kecil dari jumlah tiket terjual.',
        ];
    }
}
