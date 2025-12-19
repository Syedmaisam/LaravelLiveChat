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
            'widget_settings' => $client->widget_settings,
        ]);
    }
}
