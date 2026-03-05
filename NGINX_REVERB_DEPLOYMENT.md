# Nginx & SSL Deployment Guide for Laravel Reverb

This guide covers how to set up Laravel Reverb for real-time communication over SSL (WSS) on your Nginx server.

---

## 1. Environment Configuration (.env)

Update your production `.env` file with the following settings. Note that Reverb handles the translation between your public SSL port (443) and its internal listening port (8080).

```ini
BROADCAST_CONNECTION=reverb

# Public Reverb settings (Used by the client widget)
REVERB_APP_ID=visiontech_chat_id
REVERB_APP_KEY=visiontech_chat_key
REVERB_APP_SECRET=visiontech_chat_secret
REVERB_HOST="chat.helpacademic.co.uk"
REVERB_PORT=443
REVERB_SCHEME=https

# Internal Reverb server settings (Where the process listens)
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080
```

---

## 2. Nginx VirtualHost Configuration

Update your Nginx site configuration at `/etc/nginx/sites-available/chat.helpacademic.co.uk`.

Refine your existing `/app` location block to include the correct proxy headers for WebSocket upgrades.

```nginx
server {
    server_name chat.helpacademic.co.uk;
    root /var/www/html/public;
    index index.php index.html;

    # ... Your existing Laravel and PHP blocks ...

    # REVERB WEBSOCKET PROXY (WSS -> Internal Reverb)
    location /app {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Host $http_host;
        proxy_set_header Scheme $scheme;
        proxy_set_header SERVER_PORT $server_port;
        proxy_set_header REMOTE_ADDR $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
    }

    listen 443 ssl; # managed by Certbot
    # ... Your existing SSL configuration ...
}
```

**After updating:**

```bash
sudo nginx -t
sudo systemctl restart nginx
```

---

## 3. Running Reverb (Supervisor)

Keep the Reverb server running in the background using Supervisor.

**File:** `/etc/supervisor/conf.d/reverb.conf`

```ini
[program:reverb]
process_name=%(program_name)s
directory=/var/www/html
command=php artisan reverb:start --host=0.0.0.0 --port=8080
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/reverb.log
stopasgroup=true
killasgroup=true
```

**Apply Changes:**

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start reverb
```

---

## 4. Verification

1.  **Clear Config Cache:** `php artisan config:clear`
2.  **Verify Socket:** Open your demo page (`https://chat.helpacademic.co.uk/demo`) and check the browser console.
3.  **Connection:** You should see a successful connection to `wss://chat.helpacademic.co.uk/app/...` in the Network -> WS tab.
