<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('delivery_rating');
            $table->unsignedTinyInteger('quality_rating');
            $table->unsignedTinyInteger('conformity_rating');
            $table->unsignedTinyInteger('value_rating');
            $table->decimal('average_rating', 3, 2);
            $table->text('comment');
            $table->boolean('would_buy_again');
            $table->json('tag_ids');
            $table->timestamp('published_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_versions');
    }
};
