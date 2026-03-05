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
        Schema::table('users', function (Blueprint $table) {
            $table->string('pseudo_name')->nullable()->after('name');
            $table->string('avatar')->nullable()->after('pseudo_name');
            $table->enum('status', ['online', 'offline', 'away'])->default('offline')->after('avatar');
            $table->timestamp('last_seen_at')->nullable()->after('status');
            $table->json('push_subscription')->nullable()->after('last_seen_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['pseudo_name', 'avatar', 'status', 'last_seen_at', 'push_subscription']);
        });
    }
};
