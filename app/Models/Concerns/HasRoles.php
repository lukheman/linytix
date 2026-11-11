<?php

namespace App\Models\Concerns;

use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
     * Dashboard lama tetap menjadi tujuan default agar alur admin yang sudah
     * ada tidak berubah. Organizer diarahkan ke katalog event sampai dashboard
     * organizer tersedia pada tahap berikutnya.
     */
    public function homeRouteName(): string
    {
        if ($this->isOrganizer() && ! $this->isAdmin()) {
            return 'events.index';
        }

        return 'dashboard';
    }
}
