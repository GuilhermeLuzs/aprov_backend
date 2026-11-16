<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->string('decision', 10)->nullable()->after('status');
            $table->text('resolution_note')->nullable()->after('decision');
        });

        Schema::create('moderation_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('queue', 10);
            $table->string('target_key', 40);
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['queue', 'target_key']);
        });

        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('subject', 120);
            $table->string('topic', 20);
            $table->string('status', 10)->default('open')->index();
            $table->timestamps();
        });

        Schema::create('support_ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sender', 10);
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_messages');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('moderation_assignments');
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['decision', 'resolution_note']);
        });
    }
};
