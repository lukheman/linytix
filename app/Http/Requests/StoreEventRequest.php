<?php

namespace App\Http\Requests;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', Event::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_online' => $this->boolean('is_online'),
            'require_identity' => $this->boolean('require_identity'),
            'venue_id' => $this->boolean('is_online') ? null : ($this->filled('venue_id') ? $this->input('venue_id') : null),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'is_online' => ['boolean'],
            'venue_id' => [Rule::requiredIf(fn () => ! $this->boolean('is_online')), 'nullable', 'uuid', 'exists:venues,id'],
            'online_url' => [Rule::requiredIf(fn () => $this->boolean('is_online')), 'nullable', 'url', 'max:2048'],
            'timezone' => ['required', 'timezone'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'doors_open_at' => ['nullable', 'date', 'before_or_equal:start_at'],
            'short_desc' => ['nullable', 'string', 'max:500'],
            'description' => ['required', 'string', 'max:20000'],
            'terms' => ['nullable', 'string', 'max:20000'],
            'require_identity' => ['boolean'],
            'poster' => array_merge(['required'], $this->imageRules('poster')),
            'banner' => array_merge(['nullable'], $this->imageRules('banner')),
            'gallery' => ['nullable', 'array', 'max:'.config('ticketing.event_uploads.gallery_max')],
            'gallery.*' => $this->imageRules('gallery'),
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['uuid'],
            'lineups' => ['nullable', 'array', 'max:20'],
            'lineups.*.id' => ['nullable', 'uuid'],
            'lineups.*.name' => ['nullable', 'string', 'max:255'],
            'lineups.*.photo' => array_merge(['nullable'], $this->imageRules('lineup')),
            'lineups.*.perform_at' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function imageRules(string $key): array
    {
        $maxKb = config('ticketing.event_uploads.max_kb.'.$key);
        $dimensions = config('ticketing.event_uploads.dimensions.'.$key);

        $rules = ['image', 'mimes:jpg,jpeg,png,webp', 'max:'.$maxKb];

        if ($dimensions) {
            $rules[] = 'dimensions:min_width='.$dimensions['min_width'].',min_height='.$dimensions['min_height'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Judul event wajib diisi.',
            'category_id.required' => 'Kategori event wajib dipilih.',
            'venue_id.required' => 'Venue wajib dipilih untuk event offline.',
            'online_url.required' => 'URL event online wajib diisi.',
            'start_at.required' => 'Waktu mulai wajib diisi.',
            'end_at.after' => 'Waktu selesai harus setelah waktu mulai.',
            'description.required' => 'Deskripsi event wajib diisi.',
            'poster.required' => 'Poster event wajib diunggah.',
            'poster.image' => 'Poster harus berupa gambar.',
            'poster.max' => 'Ukuran poster terlalu besar.',
            'poster.dimensions' => 'Dimensi poster terlalu kecil.',
            'banner.dimensions' => 'Dimensi banner terlalu kecil.',
            'gallery.*.dimensions' => 'Dimensi gambar galeri terlalu kecil.',
            'lineups.*.photo.dimensions' => 'Dimensi foto lineup terlalu kecil.',
        ];
    }
}
