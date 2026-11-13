<?php

namespace App\Services\Admin;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserManagementService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor, ?string $ip = null): User
    {
        return DB::transaction(function () use ($data, $actor, $ip): User {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => mb_strtolower(trim((string) $data['email'])),
                'password' => $data['password'],
                'is_active' => true,
            ]);

            $user->assignRole(Role::CUSTOMER);

            $this->audit($actor, 'user.created', $user, [
                'email' => $user->email,
                'roles' => [Role::CUSTOMER],
            ], $ip);

            return $user;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(
        User $user,
        array $data,
        ?string $password,
        User $actor,
        ?string $ip = null,
    ): User {
        return DB::transaction(function () use ($user, $data, $password, $actor, $ip): User {
            $locked = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            $before = $locked->only(['name', 'email']);

            $locked->name = $data['name'];
            $locked->email = mb_strtolower(trim((string) $data['email']));

            if (filled($password)) {
                $locked->password = $password;
            }

            $locked->save();

            $changes = [];

            foreach (['name', 'email'] as $field) {
                if ($locked->wasChanged($field)) {
                    $changes[$field] = [
                        'from' => $before[$field],
                        'to' => $locked->getAttribute($field),
                    ];
                }
            }

            $passwordChanged = $locked->wasChanged('password');

            if ($changes === [] && ! $passwordChanged) {
                return $locked;
            }

            $this->audit($actor, 'user.updated', $locked, [
                'changes' => $changes,
                'password_changed' => $passwordChanged,
            ], $ip);

            return $locked;
        });
    }

    public function setActive(
        User $user,
        bool $active,
        User $actor,
        ?string $ip = null,
    ): User {
        return DB::transaction(function () use ($user, $active, $actor, $ip): User {
            $locked = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            if (! $active) {
                $this->ensureNotSelf($locked, $actor);
                $this->ensureNotLastActiveAdmin($locked);
            }

            if ($locked->is_active === $active) {
                return $locked;
            }

            $locked->forceFill(['is_active' => $active])->save();

            $this->audit(
                $actor,
                $active ? 'user.activated' : 'user.deactivated',
                $locked,
                [
                    'email' => $locked->email,
                    'roles' => $locked->roles->pluck('name')->all(),
                ],
                $ip,
            );

            return $locked;
        });
    }

    public function delete(User $user, User $actor, ?string $ip = null): void
    {
        DB::transaction(function () use ($user, $actor, $ip): void {
            $locked = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            $this->ensureNotSelf($locked, $actor, 'user');
            $this->ensureNotLastActiveAdmin($locked, 'user');

            $snapshot = [
                'user_id' => $locked->getKey(),
                'name' => $locked->name,
                'email' => $locked->email,
                'roles' => $locked->roles->pluck('name')->all(),
            ];

            $locked->delete();

            AuditLog::query()->create([
                'user_id' => $actor->getKey(),
                'action' => 'user.deleted',
                'entity' => 'user',
                'entity_id' => $snapshot['user_id'],
                'meta' => $snapshot,
                'ip_address' => $ip,
            ]);
        });
    }

    private function ensureNotSelf(User $target, User $actor, string $field = 'status'): void
    {
        if ($target->getKey() === $actor->getKey()) {
            throw ValidationException::withMessages([
                $field => 'Anda tidak dapat menonaktifkan atau menghapus akun sendiri.',
            ]);
        }
    }

    private function ensureNotLastActiveAdmin(User $target, string $field = 'status'): void
    {
        if (! $target->is_active || ! $target->isAdmin()) {
            return;
        }

        $otherActiveAdmins = User::query()
            ->whereKeyNot($target->getKey())
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('name', Role::ADMIN))
            ->lockForUpdate()
            ->count();

        if ($otherActiveAdmins === 0) {
            throw ValidationException::withMessages([
                $field => 'Admin terakhir tidak dapat dinonaktifkan atau dihapus.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function audit(User $actor, string $action, ?User $target, array $meta, ?string $ip): void
    {
        AuditLog::query()->create([
            'user_id' => $actor->getKey(),
            'action' => $action,
            'entity' => 'user',
            'entity_id' => $target?->getKey() ?? ($meta['user_id'] ?? null),
            'meta' => $meta,
            'ip_address' => $ip,
        ]);
    }
}
