<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_coin_rule_topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('topic', 30);
            $table->unsignedSmallInteger('threshold')->nullable();
            $table->unsignedInteger('coins')->default(0);
            $table->unsignedInteger('points')->default(0);
            $table->unsignedInteger('xp')->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'topic']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_coin_rule_topics');
    }
};
