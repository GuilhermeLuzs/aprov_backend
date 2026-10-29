<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('review_media', function (Blueprint $table) {
            $table->boolean('valid')->default(true)->after('path');
            $table->json('ai_labels')->nullable()->after('valid');
        });
    }

    public function down(): void
    {
        Schema::table('review_media', function (Blueprint $table) {
            $table->dropColumn(['valid', 'ai_labels']);
        });
    }
};
