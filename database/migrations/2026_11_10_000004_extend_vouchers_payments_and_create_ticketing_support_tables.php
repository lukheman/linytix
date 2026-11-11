<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('paid_at');
        });

        Schema::table('vouchers', function (Blueprint $table) {
            $table->unsignedBigInteger('max_discount')->nullable()->after('discount_value');
            $table->unsignedBigInteger('min_purchase')->default(0)->after('max_discount');
            $table->unsignedInteger('usage_limit')->nullable()->after('min_purchase');
            $table->unsignedInteger('per_user_limit')->nullable()->after('usage_limit');
            $table->unsignedInteger('used_count')->default(0)->after('per_user_limit');
            $table->timestamp('valid_from')->nullable()->after('used_count');
            $table->timestamp('valid_until')->nullable()->after('valid_from');
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('amount');
            $table->text('reason');
            $table->string('status')->default('requested');
            $table->string('gateway_ref')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('gateway');
            $table->string('external_id');
            $table->string('event_type')->nullable();
            $table->json('payload')->nullable();
            $table->boolean('signature_valid')->default(false);
            $table->string('status')->default('received');
            $table->timestamp('processed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->unique(['gateway', 'external_id']);
        });

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('channel');
            $table->string('recipient');
            $table->string('template')->nullable();
            $table->string('status')->default('pending');
            $table->string('provider_ref')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('webhook_events');
        Schema::dropIfExists('refunds');

        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn([
                'max_discount',
                'min_purchase',
                'usage_limit',
                'per_user_limit',
                'used_count',
                'valid_from',
                'valid_until',
            ]);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('expires_at');
        });
    }
};
