<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_company', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['client_id', 'company_id']);
        });

        $existing = DB::table('companies')->pluck('id')->all();

        foreach (DB::table('clients')->whereNotNull('followed_company_ids')->get(['id', 'followed_company_ids']) as $client) {
            $ids = collect(json_decode($client->followed_company_ids, true) ?? [])
                ->map(fn ($id) => (int) ltrim((string) $id, 'c'))
                ->filter(fn ($id) => in_array($id, $existing, true))
                ->unique();

            foreach ($ids as $companyId) {
                DB::table('client_company')->insert([
                    'client_id' => $client->id,
                    'company_id' => $companyId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('followed_company_ids');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->json('followed_company_ids')->nullable();
        });

        foreach (DB::table('client_company')->get()->groupBy('client_id') as $clientId => $rows) {
            DB::table('clients')->where('id', $clientId)->update([
                'followed_company_ids' => json_encode($rows->pluck('company_id')->all()),
            ]);
        }

        Schema::dropIfExists('client_company');
    }
};
