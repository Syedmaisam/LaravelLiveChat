<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->string('label')->nullable()->after('status'); // new, pending, converted, no_response, closed
            $table->timestamp('last_message_at')->nullable()->after('label');
            $table->integer('unread_count')->default(0)->after('last_message_at');
        });
    }

    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->dropColumn(['label', 'last_message_at', 'unread_count']);
        });
    }
};
