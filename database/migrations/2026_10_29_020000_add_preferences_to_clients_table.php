<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->json('preferred_categories')->nullable()->after('birth_date');
            $table->string('discovery_source', 20)->nullable()->after('preferred_categories');
            $table->json('followed_company_ids')->nullable()->after('discovery_source');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['preferred_categories', 'discovery_source', 'followed_company_ids']);
        });
    }
};
