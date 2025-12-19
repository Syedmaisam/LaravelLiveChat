<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('pseudo_names')->nullable()->after('pseudo_name');
            $table->string('active_pseudo_name')->nullable()->after('pseudo_names');
        });

        // Migrate existing pseudo_name to pseudo_names array
        DB::table('users')->whereNotNull('pseudo_name')->get()->each(function ($user) {
            DB::table('users')->where('id', $user->id)->update([
                'pseudo_names' => json_encode([$user->pseudo_name]),
                'active_pseudo_name' => $user->pseudo_name,
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['pseudo_names', 'active_pseudo_name']);
        });
    }
};
