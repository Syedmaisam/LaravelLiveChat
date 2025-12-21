<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
            $table->unique('uuid');
        });

        // Generate UUIDs for existing chats
        DB::table('chats')->whereNull('uuid')->get()->each(function ($chat) {
            DB::table('chats')
                ->where('id', $chat->id)
                ->update(['uuid' => (string) Str::uuid()]);
        });

        // Make uuid non-nullable after populating
        Schema::table('chats', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
