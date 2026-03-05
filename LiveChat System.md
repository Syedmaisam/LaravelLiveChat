# VisionTech LiveChat System Overview

**Date:** 2026-01-15
**Status:** Production Ready (v1.0)
**Current Role:** Real-Time Support & Engagement Platform

---

## 1. Executive Summary

VisionTechChat is a robust, multi-tenant, real-time engagement platform. It supports full WebSocket-based communication, visitor tracking, and agent collaboration. It is currently **Production Ready** and technically stable.

---

## 2. Production Readiness Audit

### 🚦 Verdict: READY FOR LAUNCH

The application code is secure, scalable, and functionally complete.

### Critical "Go-Live" Configuration

Before pointing your domain to this server, ensure these 3 items are configured:

1.  **Queue Worker (Essential):**

    -   **Why:** We use WebSockets for real-time chat. Without a queue worker, the app will lag while trying to broadcast events.
    -   **Command:** `php artisan queue:work` (Run this as a daemon/service).
    -   **Config:** Set `QUEUE_CONNECTION=database` or `redis` in `.env`.

2.  **Environment Security:**

    -   Set `APP_ENV=production`.
    -   Set `APP_DEBUG=false` (Never leave true in production).
    -   Run `php artisan config:cache` and `php artisan route:cache`.

3.  **Scheduler:**
    -   **Why:** Cleans up old sessions and handles maintenance.
    -   **Cron Entry:** `* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1`

---

## 3. Current Feature Highlights (v1.0)

The system currently supports:

### ✅ Widget Experience (WhatsApp Style)

-   **Smart Auto-Scroll:** Keeps position when reading history, auto-scrolls when at bottom.
-   **Unread Badge:** "New messages" indicator when scrolled up.
-   **Optimistic UI:** Clock/Checkmark icons for message status.
-   **Proactive Toast:** "Agent Alex sent a message" bubbles appear above the closed widget.
-   **Visual Polish:** Modern shadows, gradients, and animations.

### ✅ Agent Dashboard

-   **Real-Time Sidebar:** New chats appear instantly (yellow flash) without refresh.
-   **Live Typing:** See when visitors are typing.
-   **Visitor Tracking:** See visitor's Country, Device, and Page History.
-   **Multi-Tenancy:** Agents only see data for their assigned Clients.
-   **Analytics:** Chat volume, basic response time metrics, and active visitor lists.

### ✅ Technical Core

-   **Safe State Isolation:** Prevents message duplication or overwrites during real-time sync.
-   **Exponential Backoff:** Auto-reconnects WebSockets if internet connection flickers.
-   **Security:** Full Authorization checks on every route.

---

## 4. Maintenance Commands

Keep these handy for the server admin:

```bash
# Clear all caches (Run after .env changes)
php artisan optimize:clear

# Link storage (For file uploads)
php artisan storage:link

# Restart Queue (After code updates)
php artisan queue:restart

# Build Frontend Assets
npm run build
```
