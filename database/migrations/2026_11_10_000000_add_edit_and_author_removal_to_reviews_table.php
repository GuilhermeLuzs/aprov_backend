<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->timestamp('edited_at')->nullable()->after('moderated_by');
            $table->timestamp('author_removed_at')->nullable()->after('edited_at');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn(['edited_at', 'author_removed_at']);
        });
    }
};
