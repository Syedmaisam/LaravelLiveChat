<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('canned_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete(); // null = global
            $table->foreignId('client_id')->nullable()->constrained()->cascadeOnDelete(); // null = all clients
            $table->string('shortcut', 50); // e.g., /greeting, /hours, /thanks
            $table->string('title', 100);
            $table->text('content');
            $table->string('category')->nullable(); // greeting, closing, info, etc.
            $table->boolean('is_global')->default(false); // true = available to all agents
            $table->integer('usage_count')->default(0);
            $table->timestamps();
            
            $table->index(['user_id', 'shortcut']);
            $table->index('is_global');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canned_responses');
    }
};
