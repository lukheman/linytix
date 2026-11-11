<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => Role::CUSTOMER, 'label' => 'Customer', 'description' => 'Pembeli tiket terdaftar.'],
            ['name' => Role::ORGANIZER, 'label' => 'Organizer', 'description' => 'Penyelenggara event.'],
            ['name' => Role::STAFF, 'label' => 'Staff Lapangan', 'description' => 'Petugas scan tiket di lokasi event.'],
            ['name' => Role::ADMIN, 'label' => 'Administrator', 'description' => 'Pengelola platform.'],
        ];

        foreach ($roles as $role) {
            Role::query()->updateOrCreate(['name' => $role['name']], $role);
        }

        $admin = User::query()->where('email', 'admin@gmail.com')->first();

        if ($admin) {
            $admin->assignRole(Role::ADMIN);
            $admin->forceFill(['is_active' => true])->save();
        }

        User::query()
            ->whereDoesntHave('roles')
            ->get()
            ->each(fn (User $user) => $user->assignRole(Role::CUSTOMER));
    }
}
