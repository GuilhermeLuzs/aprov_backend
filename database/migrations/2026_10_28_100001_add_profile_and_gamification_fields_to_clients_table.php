<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->text('bio')->nullable()->after('birth_date');
            $table->unsignedInteger('points_balance')->default(0)->after('bio');
            $table->unsignedInteger('xp')->default(0)->after('points_balance');
            $table->unsignedSmallInteger('level')->default(1)->after('xp');
            $table->string('collections_visibility', 20)->default('public')->after('level');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['bio', 'points_balance', 'xp', 'level', 'collections_visibility']);
        });
    }
};
