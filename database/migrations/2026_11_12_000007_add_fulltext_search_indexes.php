<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEXES = [
        'items' => 'title',
        'companies' => 'name',
        'users' => 'name',
        'customers' => 'username',
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => $column) {
            Schema::table($table, function (Blueprint $blueprint) use ($column) {
                $blueprint->fullText($column);
            });
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $table => $column) {
            Schema::table($table, function (Blueprint $blueprint) use ($column) {
                $blueprint->dropFullText([$column]);
            });
        }
    }
};
