<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('title', 60);
            $table->string('description', 300)->nullable();
            $table->unsignedInteger('coin_cost');
            $table->date('valid_until');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('coupon_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('coupon_id')->constrained();
            $table->string('code', 60);
            $table->unsignedInteger('coins_spent');
            $table->uuid('idempotency_key');
            $table->timestamps();
            $table->unique(['customer_id', 'idempotency_key']);
        });

        Schema::create('coupon_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->string('code', 60);
            $table->foreignId('coupon_redemption_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->unique(['coupon_id', 'code']);
        });

        Schema::table('coin_transactions', function (Blueprint $table) {
            $table->foreign('coupon_redemption_id')->references('id')->on('coupon_redemptions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('coin_transactions', function (Blueprint $table) {
            $table->dropForeign(['coupon_redemption_id']);
        });
        Schema::dropIfExists('coupon_codes');
        Schema::dropIfExists('coupon_redemptions');
        Schema::dropIfExists('coupons');
    }
};
