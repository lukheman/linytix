<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class RegisterOrganizerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->is_active
            && $user->hasVerifiedEmail();
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        foreach ([
            'name',
            'description',
            'contact_email',
            'contact_phone',
            'bank_name',
            'bank_account_no',
            'bank_account_name',
        ] as $field) {
            if ($this->filled($field)) {
                $data[$field] = trim((string) $this->input($field));
            }
        }

        if (isset($data['contact_email'])) {
            $data['contact_email'] = Str::lower($data['contact_email']);
        }

        $this->merge($data);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'contact_email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['required', 'string', 'max:30'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_no' => ['nullable', 'string', 'max:50'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'agree_terms' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama organizer wajib diisi.',
            'name.max' => 'Nama organizer maksimal 255 karakter.',
            'description.max' => 'Deskripsi maksimal 5.000 karakter.',
            'contact_email.required' => 'Email kontak wajib diisi.',
            'contact_email.email' => 'Email kontak tidak valid.',
            'contact_phone.required' => 'Nomor telepon kontak wajib diisi.',
            'contact_phone.max' => 'Nomor telepon maksimal 30 karakter.',
            'bank_name.max' => 'Nama bank maksimal 255 karakter.',
            'bank_account_no.max' => 'Nomor rekening maksimal 50 karakter.',
            'bank_account_name.max' => 'Nama pemilik rekening maksimal 255 karakter.',
            'agree_terms.accepted' => 'Anda harus menyetujui syarat dan ketentuan organizer.',
        ];
    }
}
