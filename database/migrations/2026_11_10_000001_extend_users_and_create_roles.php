<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('google_id')->nullable()->after('phone');
            $table->boolean('is_active')->default(true)->after('avatar');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unique('google_id');
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['role_id', 'user_id']);
        });

        $now = now();
        $roles = [
            ['name' => 'customer', 'label' => 'Customer', 'description' => 'Pembeli tiket terdaftar.'],
            ['name' => 'organizer', 'label' => 'Organizer', 'description' => 'Penyelenggara event.'],
            ['name' => 'staff', 'label' => 'Staff Lapangan', 'description' => 'Petugas scan tiket di lokasi event.'],
            ['name' => 'admin', 'label' => 'Administrator', 'description' => 'Pengelola platform.'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['name' => $role['name']],
                $role + ['created_at' => $now, 'updated_at' => $now],
            );
        }

        // Backfill: seluruh user lama menjadi customer, admin@gmail.com menjadi admin.
        $customerRoleId = DB::table('roles')->where('name', 'customer')->value('id');
        $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id');

        foreach (DB::table('users')->orderBy('id')->cursor() as $user) {
            $roleId = $user->email === 'admin@gmail.com' ? $adminRoleId : $customerRoleId;

            DB::table('role_user')->updateOrInsert(
                ['role_id' => $roleId, 'user_id' => $user->id],
                ['created_at' => $now, 'updated_at' => $now],
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['google_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'google_id', 'is_active', 'last_login_at']);
        });
    }
};
