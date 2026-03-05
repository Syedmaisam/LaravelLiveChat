<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auto_greetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('message');
            $table->string('trigger_type'); // time_on_page, page_url, scroll_depth, exit_intent
            $table->json('trigger_conditions'); // {"seconds": 30} or {"url_contains": "/pricing"} etc.
            $table->boolean('is_active')->default(true);
            $table->integer('delay_seconds')->default(0); // delay before showing
            $table->integer('cooldown_hours')->default(24); // don't show again for X hours
            $table->integer('priority')->default(0); // higher priority triggers first
            $table->integer('shown_count')->default(0);
            $table->integer('clicked_count')->default(0);
            $table->timestamps();
            
            $table->index(['client_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_greetings');
    }
};
