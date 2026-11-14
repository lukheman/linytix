<?php

namespace App\Services\Organizer;

use App\Models\AuditLog;
use App\Models\Event;
use App\Models\TicketType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TicketTypeService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Event $event, array $data, ?User $actor = null, ?string $ip = null): TicketType
    {
        $this->ensureEditable($event);
        $this->ensureCapacity($event, $event->ticketQuotaTotal() + (int) $data['quota']);

        $ticketType = DB::transaction(function () use ($event, $data): TicketType {
            return $event->ticketTypes()->create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'price' => (int) $data['price'],
                'quota' => (int) $data['quota'],
                'sold_count' => 0,
                'min_per_order' => (int) ($data['min_per_order'] ?? 1),
                'max_per_order' => (int) ($data['max_per_order'] ?? 4),
                'sale_start_at' => $this->toUtc($data['sale_start_at'] ?? null, $event->timezoneLabel()),
                'sale_end_at' => $this->toUtc($data['sale_end_at'] ?? null, $event->timezoneLabel()),
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);
        });

        $this->refreshMinPrice($event);

        $this->audit($actor, 'ticket_type.created', $ticketType, [
            'event_id' => $event->getKey(),
            'name' => $ticketType->name,
            'price' => $ticketType->price,
            'quota' => $ticketType->quota,
            'is_active' => $ticketType->is_active,
        ], $ip);

        return $ticketType;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(TicketType $ticketType, array $data, ?User $actor = null, ?string $ip = null): TicketType
    {
        $event = $ticketType->event;

        $this->ensureEditable($event);

        if ((int) $data['quota'] < $ticketType->sold_count) {
            throw ValidationException::withMessages([
                'quota' => 'Kuota tidak boleh lebih kecil dari jumlah tiket yang sudah terjual ('.$ticketType->sold_count.').',
            ]);
        }

        $this->ensureCapacity(
            $event,
            $event->ticketQuotaTotal($ticketType->getKey()) + (int) $data['quota'],
        );

        $before = $ticketType->only([
            'name',
            'description',
            'price',
            'quota',
            'min_per_order',
            'max_per_order',
            'sale_start_at',
            'sale_end_at',
            'is_active',
        ]);

        $ticketType->fill([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price' => (int) $data['price'],
            'quota' => (int) $data['quota'],
            'min_per_order' => (int) ($data['min_per_order'] ?? 1),
            'max_per_order' => (int) ($data['max_per_order'] ?? 4),
            'sale_start_at' => $this->toUtc($data['sale_start_at'] ?? null, $event->timezoneLabel()),
            'sale_end_at' => $this->toUtc($data['sale_end_at'] ?? null, $event->timezoneLabel()),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ])->save();

        $this->refreshMinPrice($event);

        $changes = [];

        foreach ($before as $field => $oldValue) {
            $newValue = $ticketType->getAttribute($field);

            if ($oldValue != $newValue) {
                $changes[$field] = [
                    'from' => $oldValue,
                    'to' => $newValue,
                ];
            }
        }

        $this->audit($actor, 'ticket_type.updated', $ticketType, [
            'event_id' => $event->getKey(),
            'name' => $ticketType->name,
            'changes' => $changes,
        ], $ip);

        return $ticketType->refresh();
    }

    /**
     * Ubah kuota dan jadwal jual tanpa mengubah harga atau nama tiket.
     * Boleh dilakukan kapan pun selama event tidak dibatalkan.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateSales(
        TicketType $ticketType,
        array $data,
        ?User $actor = null,
        ?string $ip = null,
    ): TicketType {
        $event = $ticketType->event;

        if (! $event->canAdjustSalesSettings()) {
            throw ValidationException::withMessages([
                'status' => 'Kuota dan jadwal jual tidak dapat diubah untuk event berstatus '.$event->statusLabel().'.',
            ]);
        }

        return DB::transaction(function () use ($ticketType, $event, $data, $actor, $ip): TicketType {
            $locked = TicketType::query()
                ->whereKey($ticketType->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $data['quota'] < $locked->sold_count) {
                throw ValidationException::withMessages([
                    'quota' => 'Kuota tidak boleh lebih kecil dari jumlah tiket yang sudah terjual ('.$locked->sold_count.').',
                ]);
            }

            $this->ensureCapacity(
                $event,
                $event->ticketQuotaTotal($locked->getKey()) + (int) $data['quota'],
            );

            $before = $locked->only([
                'quota',
                'sale_start_at',
                'sale_end_at',
                'is_active',
            ]);

            $locked->fill([
                'quota' => (int) $data['quota'],
                'sale_start_at' => $this->toUtc($data['sale_start_at'] ?? null, $event->timezoneLabel()),
                'sale_end_at' => $this->toUtc($data['sale_end_at'] ?? null, $event->timezoneLabel()),
                'is_active' => (bool) ($data['is_active'] ?? true),
            ])->save();

            $changes = [];

            foreach ($before as $field => $oldValue) {
                $newValue = $locked->getAttribute($field);

                if ($oldValue != $newValue) {
                    $changes[$field] = [
                        'from' => $oldValue,
                        'to' => $newValue,
                    ];
                }
            }

            $this->audit($actor, 'ticket_type.sales_updated', $locked, [
                'event_id' => $event->getKey(),
                'name' => $locked->name,
                'changes' => $changes,
            ], $ip);

            return $locked->refresh();
        });
    }

    public function delete(TicketType $ticketType, ?User $actor = null, ?string $ip = null): void
    {
        $event = $ticketType->event;

        $this->ensureEditable($event);

        if ($ticketType->sold_count > 0) {
            throw ValidationException::withMessages([
                'ticket' => 'Jenis tiket yang sudah memiliki penjualan tidak dapat dihapus. Nonaktifkan saja.',
            ]);
        }

        $meta = [
            'event_id' => $event->getKey(),
            'ticket_type_id' => $ticketType->getKey(),
            'name' => $ticketType->name,
            'price' => $ticketType->price,
            'quota' => $ticketType->quota,
        ];

        $ticketType->delete();

        $this->refreshMinPrice($event);

        $this->audit($actor, 'ticket_type.deleted', null, $meta, $ip);
    }

    public function refreshMinPrice(Event $event): void
    {
        $minPrice = (int) ($event->ticketTypes()
            ->where('is_active', true)
            ->min('price') ?? 0);

        $event->forceFill(['min_price' => $minPrice])->save();
    }

    private function ensureEditable(Event $event): void
    {
        if (! $event->isFullyEditable()) {
            throw ValidationException::withMessages([
                'status' => 'Jenis tiket tidak dapat diubah setelah event diajukan untuk review.',
            ]);
        }
    }

    private function ensureCapacity(Event $event, int $projectedQuotaTotal): void
    {
        $overflow = $event->capacityOverflowFor($projectedQuotaTotal);

        if ($overflow) {
            throw ValidationException::withMessages([
                'quota' => 'Total kuota tiket ('.$overflow['total'].') melebihi kapasitas venue ('.$overflow['capacity'].').',
            ]);
        }
    }

    private function toUtc(?string $value, string $timezone): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        return Carbon::parse($value, $timezone)->utc();
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function audit(?User $user, string $action, ?TicketType $ticketType, array $meta, ?string $ip): void
    {
        AuditLog::query()->create([
            'user_id' => $user?->getKey(),
            'action' => $action,
            'entity' => 'ticket_type',
            'entity_id' => $ticketType?->getKey() ?? ($meta['ticket_type_id'] ?? null),
            'meta' => $meta,
            'ip_address' => $ip,
        ]);
    }
}
