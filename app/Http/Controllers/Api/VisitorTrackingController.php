<?php

namespace App\Http\Controllers\Api;

use App\Events\VisitorJoined;
use App\Events\VisitorOnlineStatusChanged;
use App\Events\VisitorPageChanged;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Visitor;
use App\Models\VisitorPageVisit;
use App\Models\VisitorSession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VisitorTrackingController extends Controller
{
    public function track(Request $request)
    {
        $request->validate([
            'widget_key' => 'required|string',
            'visitor_key' => 'nullable|uuid',
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'page_url' => 'required|string',
            'page_title' => 'nullable|string',
            'referrer_url' => 'nullable|string',
        ]);

        $client = Client::where('widget_key', $request->widget_key)
            ->where('is_active', true)
            ->firstOrFail();

        // Get or create visitor
        $visitor = null;
        if ($request->visitor_key) {
            $visitor = Visitor::where('visitor_key', $request->visitor_key)
                ->where('client_id', $client->id)
                ->first();
        }

        if (!$visitor) {
            // Get geolocation data from IP
            $geoData = $this->getGeoLocation($request->ip());
            
            $visitor = Visitor::create([
                'visitor_key' => Str::uuid(),
                'client_id' => $client->id,
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'ip_address' => $request->ip(),
                'country' => $geoData['country'] ?? null,
                'country_code' => $geoData['countryCode'] ?? null,
                'city' => $geoData['city'] ?? null,
                'timezone' => $geoData['timezone'] ?? null,
                'isp' => $geoData['isp'] ?? null,
                'device' => $this->detectDevice($request->userAgent()),
                'browser' => $this->detectBrowser($request->userAgent()),
                'os' => $this->detectOS($request->userAgent()),
                'first_visit_at' => now(),
                'last_visit_at' => now(),
                'total_visits' => 1,
            ]);

            // Broadcast visitor joined event
            event(new VisitorJoined($visitor));
        } else {
            $visitor->update([
                'name' => $request->name ?? $visitor->name,
                'email' => $request->email ?? $visitor->email,
                'phone' => $request->phone ?? $visitor->phone,
                'last_visit_at' => now(),
                'total_visits' => $visitor->total_visits + 1,
            ]);
        }

        // Get or create session
        $session = VisitorSession::where('visitor_id', $visitor->id)
            ->where('is_online', true)
            ->latest()
            ->first();

        if (!$session) {
            $session = VisitorSession::create([
                'visitor_id' => $visitor->id,
                'client_id' => $client->id,
                'session_key' => Str::uuid(),
                'referrer_url' => $request->referrer_url,
                'landing_page' => $request->page_url,
                'current_page' => $request->page_url,
                'is_online' => true,
                'started_at' => now(),
                'last_activity_at' => now(),
            ]);

            event(new VisitorOnlineStatusChanged($session, true));
        } else {
            $session->update([
                'current_page' => $request->page_url,
                'last_activity_at' => now(),
            ]);
        }

        // Track page visit (avoid duplicates for reloads)
        $lastVisit = VisitorPageVisit::where('visitor_session_id', $session->id)
            ->latest('visited_at')
            ->first();

        if ($lastVisit && $lastVisit->page_url === $request->page_url) {
            $lastVisit->update(['visited_at' => now()]);
        } else {
            VisitorPageVisit::create([
                'visitor_session_id' => $session->id,
                'page_url' => $request->page_url,
                'page_title' => $request->page_title,
                'visited_at' => now(),
            ]);
        }

        // Broadcast page change
        event(new VisitorPageChanged($session, $request->page_url, $request->page_title));

        return response()->json([
            'visitor_key' => $visitor->visitor_key,
            'session_key' => $session->session_key,
            'visitor' => [
                'id' => $visitor->id,
                'name' => $visitor->name,
            ],
        ]);
    }

    public function createSession(Request $request)
    {
        $request->validate([
            'visitor_key' => 'required|uuid',
            'widget_key' => 'required|string',
        ]);

        $client = Client::where('widget_key', $request->widget_key)
            ->where('is_active', true)
            ->firstOrFail();

        $visitor = Visitor::where('visitor_key', $request->visitor_key)
            ->where('client_id', $client->id)
            ->firstOrFail();

        // Mark old sessions as offline
        VisitorSession::where('visitor_id', $visitor->id)
            ->where('is_online', true)
            ->update(['is_online' => false]);

        $session = VisitorSession::create([
            'visitor_id' => $visitor->id,
            'client_id' => $client->id,
            'session_key' => Str::uuid(),
            'is_online' => true,
            'started_at' => now(),
            'last_activity_at' => now(),
        ]);

        event(new VisitorOnlineStatusChanged($session, true));

        return response()->json([
            'session_key' => $session->session_key,
        ]);
    }

    public function getStatus(Request $request)
    {
        $request->validate([
            'visitor_key' => 'required|uuid',
        ]);

        $visitor = Visitor::where('visitor_key', $request->visitor_key)->first();

        if (!$visitor) {
            return response()->json(['status' => 'not_found'], 404);
        }

        $session = VisitorSession::where('visitor_id', $visitor->id)
            ->where('is_online', true)
            ->latest()
            ->first();

        return response()->json([
            'visitor' => $visitor,
            'session' => $session,
            'is_online' => $session !== null,
        ]);
    }

    public function trackPageVisit(Request $request)
    {
        $request->validate([
            'session_key' => 'required|uuid',
            'page_url' => 'required|string',
            'page_title' => 'nullable|string',
            'time_spent' => 'nullable|integer',
        ]);

        $session = VisitorSession::where('session_key', $request->session_key)
            ->where('is_online', true)
            ->firstOrFail();

        $session->update([
            'current_page' => $request->page_url,
            'last_activity_at' => now(),
        ]);

        VisitorPageVisit::create([
            'visitor_session_id' => $session->id,
            'page_url' => $request->page_url,
            'page_title' => $request->page_title,
            'time_spent' => $request->time_spent ?? 0,
            'visited_at' => now(),
        ]);

        event(new VisitorPageChanged($session, $request->page_url, $request->page_title));

        return response()->json(['success' => true]);
    }

    /**
     * Heartbeat to keep visitor session alive
     */
    public function heartbeat(Request $request)
    {
        $request->validate([
            'session_key' => 'required|uuid',
        ]);

        $session = VisitorSession::where('session_key', $request->session_key)->first();

        if (!$session) {
            return response()->json(['error' => 'Session not found'], 404);
        }

        $wasOffline = !$session->is_online;
        
        $session->update([
            'is_online' => true,
            'last_activity_at' => now(),
        ]);

        // Broadcast if visitor came back online
        if ($wasOffline) {
            event(new VisitorOnlineStatusChanged($session, true));
        }

        // Clean up stale sessions (offline after 2 minutes of inactivity)
        $this->cleanupStaleSessions();

        return response()->json(['success' => true]);
    }

    /**
     * Mark stale sessions as offline (1 minute of inactivity = 2 missed heartbeats)
     */
    private function cleanupStaleSessions()
    {
        $oneMinuteAgo = now()->subMinutes(1);
        
        $staleSessions = VisitorSession::where('is_online', true)
            ->where('last_activity_at', '<', $oneMinuteAgo)
            ->get();

        foreach ($staleSessions as $session) {
            $session->update([
                'is_online' => false,
            ]);
            
            event(new VisitorOnlineStatusChanged($session, false));
        }
    }

    /**
     * Mark visitor as offline (called on page unload/visibility change)
     */
    public function markOffline(Request $request)
    {
        \Log::info('🔴 markOffline called', ['data' => $request->all()]);
        
        $request->validate([
            'session_key' => 'required|uuid',
        ]);

        $session = VisitorSession::where('session_key', $request->session_key)->first();
        
        \Log::info('Session lookup', ['found' => $session ? 'yes' : 'no', 'is_online' => $session?->is_online]);

        if ($session && $session->is_online) {
            $session->update([
                'is_online' => false,
                'ended_at' => now(),
            ]);
            
            \Log::info('✅ Session marked offline, broadcasting event');
            event(new VisitorOnlineStatusChanged($session, false));
        }

        return response()->json(['success' => true]);
    }

    private function detectDevice($userAgent): ?string
    {
        if (preg_match('/mobile|android|iphone|ipad/i', $userAgent)) {
            return 'mobile';
        }
        if (preg_match('/tablet|ipad/i', $userAgent)) {
            return 'tablet';
        }
        return 'desktop';
    }

    private function detectBrowser($userAgent): ?string
    {
        if (preg_match('/chrome/i', $userAgent)) return 'Chrome';
        if (preg_match('/firefox/i', $userAgent)) return 'Firefox';
        if (preg_match('/safari/i', $userAgent)) return 'Safari';
        if (preg_match('/edge/i', $userAgent)) return 'Edge';
        return 'Unknown';
    }

    private function detectOS($userAgent): ?string
    {
        if (preg_match('/windows/i', $userAgent)) return 'Windows';
        if (preg_match('/macintosh|mac os/i', $userAgent)) return 'macOS';
        if (preg_match('/linux/i', $userAgent)) return 'Linux';
        if (preg_match('/android/i', $userAgent)) return 'Android';
        if (preg_match('/iphone|ipad|ipod/i', $userAgent)) return 'iOS';
        return 'Unknown';
    }

    /**
     * Get geolocation data from IP address using ip-api.com
     */
    private function getGeoLocation(string $ip): array
    {
        // Skip for localhost/private IPs
        if (in_array($ip, ['127.0.0.1', '::1']) || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.')) {
            return [];
        }

        try {
            $response = file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country,countryCode,city,timezone,isp");
            $data = json_decode($response, true);
            
            if ($data && $data['status'] === 'success') {
                return $data;
            }
        } catch (\Exception $e) {
            // Log error but don't fail the request
            \Log::warning('Geolocation lookup failed: ' . $e->getMessage());
        }

        return [];
    }
}
