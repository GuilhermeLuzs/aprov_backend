<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['company_id', 'coins_reward', 'xp_reward']);
        });

        Schema::table('items', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('brand')->nullable()->after('title');
            $table->unsignedInteger('duration_minutes')->nullable()->after('brand');
            $table->string('service_modality', 20)->nullable()->after('duration_minutes');
            $table->string('recurrence', 20)->nullable()->after('service_modality');
            $table->char('cep', 8)->nullable()->after('recurrence');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
            $table->dropColumn(['brand', 'duration_minutes', 'service_modality', 'recurrence', 'cep', 'deleted_at']);
            $table->string('company_id')->nullable();
            $table->unsignedInteger('coins_reward')->default(0);
            $table->unsignedInteger('xp_reward')->default(0);
        });
    }
};
