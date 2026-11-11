<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'buyer_name' => trim((string) $this->input('buyer_name')),
            'buyer_email' => Str::lower(trim((string) $this->input('buyer_email'))),
            'buyer_phone' => trim((string) $this->input('buyer_phone')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ticket_type_id' => ['required', 'uuid'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'buyer_name' => ['required', 'string', 'max:255'],
            'buyer_email' => ['required', 'email', 'max:255'],
            'buyer_phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s().]+$/'],
            'holder_names' => ['nullable', 'array', 'max:100'],
            'holder_names.*' => ['nullable', 'string', 'max:255'],
            'expected_unit_price' => ['nullable', 'integer', 'min:0'],
            'idempotency_key' => ['nullable', 'uuid'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'buyer_phone.regex' => 'Nomor telepon hanya boleh berisi angka, spasi, tanda hubung, dan tanda plus.',
        ];
    }
}
