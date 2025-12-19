<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visitor_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('rating'); // 1-5 stars
            $table->text('feedback')->nullable();
            $table->timestamps();
            
            $table->unique('chat_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_ratings');
    }
};
