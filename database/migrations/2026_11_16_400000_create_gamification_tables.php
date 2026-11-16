<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accessories', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 40)->unique();
            $table->string('kind', 10);
            $table->string('asset', 30);
            $table->string('name', 60);
            $table->string('description', 160);
            $table->unsignedInteger('price');
            $table->unsignedSmallInteger('required_level')->default(1);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('accessory_customer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('accessory_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['customer_id', 'accessory_id']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('equipped_skin_id')->nullable()->constrained('accessories')->nullOnDelete();
            $table->foreignId('equipped_frame_id')->nullable()->constrained('accessories')->nullOnDelete();
        });

        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10);
            $table->unsignedInteger('amount');
            $table->foreignId('review_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('accessory_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->index(['customer_id', 'created_at']);
        });

        DB::table('review_rewards')
            ->join('reviews', 'reviews.id', '=', 'review_rewards.review_id')
            ->where('review_rewards.points', '>', 0)
            ->orderBy('review_rewards.id')
            ->select('reviews.customer_id', 'review_rewards.points', 'reviews.id as review_id', 'reviews.created_at')
            ->each(fn ($row) => DB::table('point_transactions')->insert([
                'customer_id' => $row->customer_id,
                'type' => 'earned',
                'amount' => $row->points,
                'review_id' => $row->review_id,
                'created_at' => $row->created_at,
                'updated_at' => $row->created_at,
            ]));
    }

    public function down(): void
    {
        Schema::dropIfExists('point_transactions');
        Schema::table('customers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('equipped_skin_id');
            $table->dropConstrainedForeignId('equipped_frame_id');
        });
        Schema::dropIfExists('accessory_customer');
        Schema::dropIfExists('accessories');
    }
};
