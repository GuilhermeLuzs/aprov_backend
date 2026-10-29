<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('coins')->default(0);
            $table->unsignedInteger('points')->default(0);
            $table->unsignedInteger('xp')->default(0);
            $table->json('topics_awarded');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_rewards');
    }
};
