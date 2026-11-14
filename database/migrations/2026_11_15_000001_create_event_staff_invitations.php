<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_staff_invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('event_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('gate', 100);
            $table->char('token_hash', 64)->unique();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('accepted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('expires_at');
            $table->timestampTz('accepted_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->timestampTz('last_sent_at')->nullable();
            $table->unsignedSmallInteger('send_count')->default(1);
            $table->timestamps();

            $table->index(['event_id', 'email']);
            $table->index(['event_id', 'accepted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_staff_invitations');
    }
};
