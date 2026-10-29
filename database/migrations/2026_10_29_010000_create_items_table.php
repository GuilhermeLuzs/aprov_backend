<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('company_id')->nullable()->index();
            $table->string('kind', 20);
            $table->json('categories');
            $table->string('title');
            $table->text('description');
            $table->unsignedInteger('coins_reward')->default(0);
            $table->unsignedInteger('xp_reward')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
