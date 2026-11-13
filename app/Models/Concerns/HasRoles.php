<?php

namespace App\Models\Concerns;

use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

trait HasRoles
{
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function hasRole(string $role): bool
    {
        return $this->roles->contains(fn (Role $candidate) => $candidate->name === $role);
    }

    /**
     * @param  array<int, string>  $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->roles->contains(fn (Role $candidate) => in_array($candidate->name, $roles, true));
    }

    public function assignRole(string $role): self
    {
        $roleModel = Role::query()->firstOrCreate(
            ['name' => $role],
            ['label' => ucfirst($role)],
        );

        if (! $this->roles->contains($roleModel)) {
            $this->roles()->attach($roleModel->id);
            $this->unsetRelation('roles');
        }

        return $this;
    }

    public function removeRole(string $role): self
    {
        $roleModel = Role::query()->where('name', $role)->first();

        if ($roleModel) {
            $this->roles()->detach($roleModel->id);
            $this->unsetRelation('roles');
        }

        return $this;
    }

    /**
     * @param  array<int, string>  $roles
     */
    public function syncRoles(array $roles): self
    {
        $ids = Collection::make($roles)
            ->map(fn (string $role) => Role::query()->firstOrCreate(
                ['name' => $role],
                ['label' => ucfirst($role)],
            )->id)
            ->all();

        $this->roles()->sync($ids);
        $this->unsetRelation('roles');

        return $this;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Role::ADMIN);
    }

    public function isOrganizer(): bool
    {
        return $this->hasRole(Role::ORGANIZER);
    }

    public function isStaff(): bool
    {
        return $this->hasRole(Role::STAFF);
    }

    public function isCustomer(): bool
    {
        return $this->hasRole(Role::CUSTOMER);
    }

    /**
     * Halaman awal setelah login berdasarkan peran.
     *
     * Organizer diarahkan ke dashboard organizer; admin dan peran lain tetap
     * menggunakan dashboard lama agar alur yang sudah ada tidak berubah.
     */
    public function homeRouteName(): string
    {
        if ($this->isOrganizer() && ! $this->isAdmin()) {
            return 'organizer.dashboard';
        }

        if ($this->isStaff() && ! $this->isAdmin()) {
            return 'scan.index';
        }

        return 'dashboard';
    }

    /**
     * Apakah pengguna boleh membuka URL tertentu berdasarkan middleware role.
     *
     * Dipakai agar redirect "intended" setelah login tidak melempar pengguna
     * ke halaman yang memang bukan haknya (mis. organizer ke /admin/pesanan).
     */
    public function canAccessUrl(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '/';

        // Organizer selalu diarahkan ke area organizer, bukan area admin.
        if ($this->isOrganizer() && ! $this->isAdmin() && str_starts_with($path, '/admin')) {
            return false;
        }

        try {
            $route = app('router')->getRoutes()->match(
                Request::create($url, 'GET')
            );
        } catch (\Throwable) {
            return false;
        }

        foreach ($route->gatherMiddleware() as $middleware) {
            if (! is_string($middleware) || ! str_starts_with($middleware, 'role:')) {
                continue;
            }

            $roles = array_values(array_filter(explode(',', substr($middleware, 5))));

            if ($roles !== [] && ! $this->hasAnyRole($roles)) {
                return false;
            }
        }

        return true;
    }
}
