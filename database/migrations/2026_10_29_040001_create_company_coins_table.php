<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_coins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('is_enabled')->default(true);
            $table->string('name');
            $table->string('color', 7);
            $table->string('icon', 20);
            $table->text('usage_description')->nullable();
            $table->decimal('coin_rate', 10, 2)->default(0);
            $table->decimal('real_value_rate', 10, 4)->default(0);
            $table->boolean('coupons_enabled')->default(false);
            $table->string('webhook_url')->nullable();
            $table->string('webhook_secret')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_coins');
    }
};
