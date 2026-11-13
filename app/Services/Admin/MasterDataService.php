<?php

namespace App\Services\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MasterDataService
{
    /**
     * Buat slug unik untuk kategori/venue dan tolak duplikat eksplisit.
     *
     * @param  class-string<Model>  $modelClass
     */
    public function resolveSlug(
        string $modelClass,
        ?string $input,
        string $fallback,
        mixed $ignoreId = null,
    ): string {
        $slug = Str::slug(trim((string) ($input ?: $fallback)));

        if ($slug === '') {
            $slug = 'item';
        }

        $query = $modelClass::query()->where('slug', $slug);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'slug' => 'Slug sudah digunakan.',
            ]);
        }

        return $slug;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function audit(
        User $actor,
        string $action,
        string $entity,
        mixed $entityId,
        array $meta,
        ?string $ip,
    ): void {
        AuditLog::query()->create([
            'user_id' => $actor->getKey(),
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'meta' => $meta,
            'ip_address' => $ip,
        ]);
    }
}
