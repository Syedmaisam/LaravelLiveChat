<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Widget Appearance
            $table->string('widget_color', 7)->default('#fe9e00')->after('is_active'); // hex color
            $table->string('widget_icon')->default('chat')->after('widget_color'); // chat, support, message, custom
            $table->string('widget_icon_url')->nullable()->after('widget_icon'); // custom icon URL
            $table->string('widget_position')->default('bottom-right')->after('widget_icon_url'); // bottom-right, bottom-left
            
            // Welcome Message
            $table->string('widget_welcome_title')->default('Hi there! 👋')->after('widget_position');
            $table->string('widget_welcome_message', 500)->default('How can we help you today?')->after('widget_welcome_title');
            $table->string('widget_agent_name')->default('Support Team')->after('widget_welcome_message');
            $table->string('widget_agent_avatar')->nullable()->after('widget_agent_name'); // Avatar URL
            
            // Behavior
            $table->boolean('widget_show_branding')->default(true)->after('widget_agent_avatar');
            $table->boolean('widget_auto_open')->default(false)->after('widget_show_branding');
            $table->integer('widget_auto_open_delay')->default(5)->after('widget_auto_open'); // seconds
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'widget_color',
                'widget_icon',
                'widget_icon_url',
                'widget_position',
                'widget_welcome_title',
                'widget_welcome_message',
                'widget_agent_name',
                'widget_agent_avatar',
                'widget_show_branding',
                'widget_auto_open',
                'widget_auto_open_delay',
            ]);
        });
    }
};
