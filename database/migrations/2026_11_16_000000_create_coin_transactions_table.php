<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coin_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10);
            $table->unsignedInteger('amount');
            $table->foreignId('review_id')->nullable()->constrained()->nullOnDelete();
            $table->string('topic', 30)->nullable();
            $table->unsignedBigInteger('coupon_redemption_id')->nullable()->index();
            $table->timestamps();
            $table->index(['company_id', 'created_at']);
            $table->index(['customer_id', 'created_at']);
        });

        DB::table('review_rewards')
            ->join('reviews', 'reviews.id', '=', 'review_rewards.review_id')
            ->join('items', 'items.id', '=', 'reviews.item_id')
            ->where('review_rewards.coins', '>', 0)
            ->whereNotNull('items.company_id')
            ->orderBy('review_rewards.id')
            ->select('reviews.customer_id', 'items.company_id', 'review_rewards.coins', 'reviews.id as review_id', 'reviews.created_at')
            ->each(fn ($row) => DB::table('coin_transactions')->insert([
                'customer_id' => $row->customer_id,
                'company_id' => $row->company_id,
                'type' => 'earned',
                'amount' => $row->coins,
                'review_id' => $row->review_id,
                'created_at' => $row->created_at,
                'updated_at' => $row->created_at,
            ]));
    }

    public function down(): void
    {
        Schema::dropIfExists('coin_transactions');
    }
};
