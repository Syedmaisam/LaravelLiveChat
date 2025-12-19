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
        Schema::create('analytics_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('client_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->integer('total_visitors')->default(0);
            $table->integer('total_chats')->default(0);
            $table->integer('avg_response_time')->default(0)->comment('Average in seconds');
            $table->integer('total_messages_sent')->default(0);
            $table->integer('total_files_shared')->default(0);
            $table->timestamps();

            $table->unique(['date', 'client_id', 'user_id']);
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_snapshots');
    }
};
