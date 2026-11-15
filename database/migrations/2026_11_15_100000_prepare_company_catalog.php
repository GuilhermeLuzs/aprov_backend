<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->char('ibge_code', 7);
            $table->string('city', 60);
            $table->char('state', 2);
            $table->unique(['item_id', 'ibge_code']);
        });

        Schema::table('item_media', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->change();
        });

        DB::table('item_media')
            ->join('items', 'items.id', '=', 'item_media.item_id')
            ->update(['item_media.company_id' => DB::raw('items.company_id')]);

        Schema::table('items', function (Blueprint $table) {
            $table->fullText(['title', 'description']);
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropFullText(['title', 'description']);
        });

        Schema::table('item_media', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::dropIfExists('item_cities');
    }
};
