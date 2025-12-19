<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;

class WidgetController extends Controller
{
    public function config(Request $request)
    {
        $request->validate([
            'widget_key' => 'required|string',
        ]);

        $client = Client::where('widget_key', $request->widget_key)
            ->where('is_active', true)
            ->firstOrFail();

        return response()->json([
            'widget_key' => $client->widget_key,
            'api_url' => url('/api'),
            'ws_key' => config('broadcasting.connections.reverb.key'),
            'ws_host' => config('broadcasting.connections.reverb.options.host') ?: request()->getHost(),
            'ws_port' => config('broadcasting.connections.reverb.options.port') ?: 8080,
            'ws_scheme' => config('broadcasting.connections.reverb.options.scheme') ?: 'http',
            // Widget customization settings
            'widget_color' => $client->widget_color,
            'widget_icon' => $client->widget_icon,
            'widget_icon_url' => $client->widget_icon_url,
            'widget_position' => $client->widget_position,
            'widget_welcome_title' => $client->widget_welcome_title,
            'widget_welcome_message' => $client->widget_welcome_message,
            'widget_agent_name' => $client->widget_agent_name,
            'widget_agent_avatar' => $client->widget_agent_avatar,
            'widget_show_branding' => $client->widget_show_branding,
            'widget_auto_open' => $client->widget_auto_open,
            'widget_auto_open_delay' => $client->widget_auto_open_delay,
            // Legacy
            'widget_settings' => $client->widget_settings,
        ]);
    }
}
