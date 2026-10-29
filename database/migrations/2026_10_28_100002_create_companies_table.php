<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->char('cnpj', 14)->unique();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 20);
            $table->char('zip_code', 8);
            $table->string('street');
            $table->string('number', 10);
            $table->string('complement')->nullable();
            $table->string('district');
            $table->string('city');
            $table->char('state', 2);
            $table->string('responsible_name');
            $table->char('responsible_cpf', 11);
            $table->string('responsible_phone', 20);
            $table->string('niche');
            $table->string('modality', 20);
            $table->string('size', 20);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
