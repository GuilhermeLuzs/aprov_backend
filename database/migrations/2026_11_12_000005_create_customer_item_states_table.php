<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_item_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10);
            $table->timestamps();

            $table->unique(['customer_id', 'item_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_item_states');
    }
};
