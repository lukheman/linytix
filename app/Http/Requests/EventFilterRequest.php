<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator as ValidatorFactory;

class EventFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Halaman filter publik tidak boleh gagal total karena query string
     * yang tidak valid. Nilai yang salah dibuang di filters().
     */
    protected function failedValidation(Validator $validator): void
    {
        //
    }

    /**
     * Aturan filter. Nilai yang tidak valid tidak memblokir halaman; lihat filters().
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'price_min' => ['nullable', 'integer', 'min:0'],
            'price_max' => ['nullable', 'integer', 'min:0'],
            'sort' => ['nullable', 'in:upcoming,price_asc,price_desc,newest'],
        ];
    }

    /**
     * Filter yang sudah disanitasi; input tidak valid diabaikan.
     *
     * @return array{q: ?string, category: ?string, city: ?string, date_from: ?Carbon, date_to: ?Carbon, price_min: ?int, price_max: ?int, sort: string}
     */
    public function filters(): array
    {
        $validator = ValidatorFactory::make($this->query(), $this->rules());

        if ($validator->fails()) {
            // Validasi per-field: buang hanya field yang tidak valid.
            $validated = collect($this->query())
                ->filter(function ($value, $key) {
                    $result = ValidatorFactory::make([$key => $value], [$key => $this->rules()[$key] ?? []]);

                    return $result->passes();
                })
                ->all();
        } else {
            $validated = $validator->validated();
        }

        $keyword = trim((string) ($validated['q'] ?? ''));
        $dateFrom = $this->parseDate($validated['date_from'] ?? null);
        $dateTo = $this->parseDate($validated['date_to'] ?? null);

        if ($dateFrom && $dateTo && $dateFrom->gt($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $priceMin = isset($validated['price_min']) ? (int) $validated['price_min'] : null;
        $priceMax = isset($validated['price_max']) ? (int) $validated['price_max'] : null;

        if ($priceMin !== null && $priceMax !== null && $priceMin > $priceMax) {
            [$priceMin, $priceMax] = [$priceMax, $priceMin];
        }

        return [
            'q' => mb_strlen($keyword) >= 2 ? $keyword : null,
            'category' => $validated['category'] ?? null,
            'city' => $validated['city'] ?? null,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'price_min' => $priceMin,
            'price_max' => $priceMax,
            'sort' => $validated['sort'] ?? 'upcoming',
        ];
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
