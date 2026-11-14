<?php

namespace App\Http\Requests;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;

class StaffInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');

        if (! $event instanceof Event) {
            return false;
        }

        return (bool) $this->user()?->can('manageStaff', $event);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'gate' => ['required', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Email staff wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'gate.required' => 'Gate penugasan wajib diisi.',
            'gate.max' => 'Gate penugasan maksimal 100 karakter.',
        ];
    }
}
