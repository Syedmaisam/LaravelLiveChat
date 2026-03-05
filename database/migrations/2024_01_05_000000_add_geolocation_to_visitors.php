<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            $table->string('country_code', 2)->nullable()->after('country');
            $table->string('timezone')->nullable()->after('city');
            $table->string('isp')->nullable()->after('timezone');
        });
    }

    public function down(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            $table->dropColumn(['country_code', 'timezone', 'isp']);
        });
    }
};
