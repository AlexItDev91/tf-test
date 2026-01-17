<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_action_logs', static function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('action', 64);

            $table->nullableMorphs('subject');

            $table->json('metadata')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 512)->nullable();

            $table->timestamps();

            $table->index(['user_id', 'action']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_action_logs');
    }
};
