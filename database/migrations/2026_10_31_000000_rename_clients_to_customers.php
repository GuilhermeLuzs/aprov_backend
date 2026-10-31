<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->rename(
            from: ['people' => 'clients', 'balances' => 'client_coin_balances', 'follows' => 'client_company', 'key' => 'client_id', 'role' => 'client'],
            to: ['people' => 'customers', 'balances' => 'customer_coin_balances', 'follows' => 'company_customer', 'key' => 'customer_id', 'role' => 'customer'],
        );
    }

    public function down(): void
    {
        $this->rename(
            from: ['people' => 'customers', 'balances' => 'customer_coin_balances', 'follows' => 'company_customer', 'key' => 'customer_id', 'role' => 'customer'],
            to: ['people' => 'clients', 'balances' => 'client_coin_balances', 'follows' => 'client_company', 'key' => 'client_id', 'role' => 'client'],
        );
    }

    private function rename(array $from, array $to): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
        });

        foreach (['balances', 'follows'] as $pivot) {
            Schema::table($from[$pivot], function (Blueprint $table) use ($from, $pivot) {
                $table->dropForeign([$from['key']]);
                $table->dropForeign(['company_id']);
                $table->dropUnique([$from['key'], 'company_id']);
                $table->dropIndex("{$from[$pivot]}_company_id_foreign");
            });
        }

        Schema::table($from['people'], function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id']);
            $table->dropUnique(['username']);
            $table->dropUnique(['cpf']);
        });

        Schema::rename($from['people'], $to['people']);
        Schema::rename($from['balances'], $to['balances']);
        Schema::rename($from['follows'], $to['follows']);

        Schema::table($to['people'], function (Blueprint $table) {
            $table->unique('user_id');
            $table->unique('username');
            $table->unique('cpf');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        foreach (['balances', 'follows'] as $pivot) {
            Schema::table($to[$pivot], function (Blueprint $table) use ($from, $to) {
                $table->renameColumn($from['key'], $to['key']);
            });

            Schema::table($to[$pivot], function (Blueprint $table) use ($to) {
                $table->unique([$to['key'], 'company_id']);
                $table->foreign($to['key'])->references('id')->on($to['people'])->cascadeOnDelete();
                $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            });
        }

        Schema::table('reviews', function (Blueprint $table) use ($to) {
            $table->foreign('customer_id')->references('id')->on($to['people'])->cascadeOnDelete();
        });

        DB::table('users')->where('role', $from['role'])->update(['role' => $to['role']]);
    }
};
