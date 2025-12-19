<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('visitor_id')->constrained()->onDelete('cascade');
            $table->foreignId('visitor_session_id')->nullable()->constrained('visitor_sessions')->onDelete('set null');
            $table->enum('status', ['waiting', 'active', 'closed'])->default('waiting');
            $table->boolean('lead_form_filled')->default(false);
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();
            $table->enum('ended_by', ['agent', 'visitor'])->nullable();
            $table->timestamps();

            $table->index('client_id');
            $table->index('visitor_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
