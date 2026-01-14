<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add sender_name column to store the agent's pseudo name at the time of sending
     * This ensures that if an agent changes their pseudo name mid-conversation,
     * old messages keep the old name while new messages show the new name.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('sender_name')->nullable()->after('sender_id')
                  ->comment('Name used at send time (for agent pseudo name support)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('sender_name');
        });
    }
};
