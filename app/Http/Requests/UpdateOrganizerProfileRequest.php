<?php

namespace App\Http\Requests;

use App\Models\Organizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateOrganizerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isOrganizer();
    }

    protected function prepareForValidation(): void
    {
        $nullable = [
            'description',
            'logo_url',
            'contact_email',
            'contact_phone',
            'bank_name',
            'bank_account_no',
            'bank_account_name',
        ];

        $data = [];

        foreach ($nullable as $field) {
            if ($this->filled($field)) {
                $data[$field] = trim((string) $this->input($field));
            } elseif ($this->exists($field)) {
                $data[$field] = null;
            }
        }

        if ($this->filled('name')) {
            $data['name'] = trim((string) $this->input('name'));
        }

        if ($this->filled('slug')) {
            $data['slug'] = Str::slug((string) $this->input('slug'));
        }

        if ($this->filled('contact_email')) {
            $data['contact_email'] = Str::lower((string) $this->input('contact_email'));
        }

        $this->merge($data);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizer = Organizer::query()
            ->where('owner_user_id', $this->user()->getKey())
            ->first();

        $organizerId = $organizer?->getKey();

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('organizers', 'slug')->ignore($organizerId),
                Rule::unique('organizer_slug_redirects', 'slug')
                    ->where(fn ($query) => $query->where('organizer_id', '!=', $organizerId)),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'logo_url' => ['nullable', 'url', 'max:2048'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_no' => ['nullable', 'string', 'max:50'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama organizer wajib diisi.',
            'slug.required' => 'Slug wajib diisi.',
            'slug.unique' => 'Slug ini sudah digunakan organizer lain.',
            'slug.alpha_dash' => 'Slug hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
            'logo_url.url' => 'URL logo tidak valid.',
            'contact_email.email' => 'Email kontak tidak valid.',
        ];
    }
}
