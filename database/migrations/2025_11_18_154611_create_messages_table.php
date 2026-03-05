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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->constrained()->onDelete('cascade');
            $table->enum('sender_type', ['agent', 'visitor']);
            $table->unsignedBigInteger('sender_id')->comment('user_id if agent, visitor_id if visitor');
            $table->enum('message_type', ['text', 'file'])->default('text');
            $table->text('message')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->integer('file_size')->nullable()->comment('Size in bytes');
            $table->string('file_type')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();

            $table->index('chat_id');
            $table->index(['sender_type', 'sender_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
