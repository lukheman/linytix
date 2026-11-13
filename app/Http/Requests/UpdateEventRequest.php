<?php

namespace App\Http\Requests;

use App\Models\Event;

class UpdateEventRequest extends StoreEventRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');

        if (! $event instanceof Event) {
            return false;
        }

        if (! $event->isFullyEditable() && ! $event->allowsNonCriticalEdits()) {
            return false;
        }

        return (bool) $this->user()?->can('update', $event);
    }

    protected function prepareForValidation(): void
    {
        $event = $this->route('event');

        if ($event instanceof Event && $event->isFullyEditable()) {
            parent::prepareForValidation();
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $event = $this->route('event');

        if ($event instanceof Event && ! $event->isFullyEditable()) {
            return [
                'description' => ['nullable', 'string', 'max:20000'],
                'gallery' => ['nullable', 'array', 'max:'.config('ticketing.event_uploads.gallery_max')],
                'gallery.*' => $this->imageRules('gallery'),
                'remove_images' => ['nullable', 'array'],
                'remove_images.*' => ['uuid'],
            ];
        }

        $rules = parent::rules();
        $rules['poster'] = array_merge(['nullable'], $this->imageRules('poster'));

        return $rules;
    }
}
