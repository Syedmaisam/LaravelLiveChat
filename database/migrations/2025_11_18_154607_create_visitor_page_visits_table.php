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
        Schema::create('visitor_page_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_session_id')->constrained('visitor_sessions')->onDelete('cascade');
            $table->string('page_url');
            $table->string('page_title')->nullable();
            $table->integer('time_spent')->default(0)->comment('Time in seconds');
            $table->timestamp('visited_at')->useCurrent();
            $table->timestamps();

            $table->index('visitor_session_id');
            $table->index('visited_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitor_page_visits');
    }
};
