<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SERVICE_SKIPS = ['sent_video', 'photo_in_use'];

    public function up(): void
    {
        Schema::table('company_coin_rule_topics', function (Blueprint $table) {
            $table->string('kind', 10)->default('product')->after('company_id');
        });

        Schema::table('company_coin_rule_topics', function (Blueprint $table) {
            $table->unique(['company_id', 'kind', 'topic']);
            $table->dropUnique(['company_id', 'topic']);
        });

        $now = now();
        $products = DB::table('company_coin_rule_topics')->where('kind', 'product')->get();

        foreach ($products->whereNotIn('topic', self::SERVICE_SKIPS) as $rule) {
            DB::table('company_coin_rule_topics')->insert([
                'company_id' => $rule->company_id,
                'kind' => 'service',
                'topic' => $rule->topic,
                'threshold' => $rule->threshold,
                'coins' => $rule->coins,
                'points' => $rule->points,
                'xp' => $rule->xp,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ($products->pluck('company_id')->unique() as $companyId) {
            DB::table('company_coin_rule_topics')->insert([
                'company_id' => $companyId,
                'kind' => 'product',
                'topic' => 'photo_in_use',
                'threshold' => null,
                'coins' => 0,
                'points' => 0,
                'xp' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('company_coin_rule_topics')->where('kind', 'service')->delete();
        DB::table('company_coin_rule_topics')->where('topic', 'photo_in_use')->delete();

        Schema::table('company_coin_rule_topics', function (Blueprint $table) {
            $table->unique(['company_id', 'topic']);
            $table->dropUnique(['company_id', 'kind', 'topic']);
            $table->dropColumn('kind');
        });
    }
};
