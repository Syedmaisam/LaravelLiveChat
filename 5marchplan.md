# 5 March Changes — Implementation Plan

## Overview

This plan covers 6 enhancements across the **Visitor Page** (`monitoring.blade.php`) and **Chat Page** (`chat.blade.php` / `inbox.blade.php`), plus the shared **Navigation** (`dashboard.blade.php`).

---

## Visitor Page Changes

### 1. Differentiate "Already Joined Chat" vs "New Chat"

**Current:** Every visitor card shows the same gold "Start Chat" button regardless of chat status.

**Proposed Change:**

- In `DashboardController.monitoring()`, eager-load each visitor session's active chats and their participants:
    ```php
    ->with(['visitor', 'client', 'chats' => function($q) {
        $q->where('status', '!=', 'closed')->with('participants');
    }])
    ```
- In `monitoring.blade.php`, check if `$session->chats->isNotEmpty()`:
    - **If chat exists (already joined):**
        - Show a **green-styled** badge/button: "Chat Active" or "Continue Chat" linking to the chat page.
        - Make visitor card border green-tinted.
    - **If no chat (new visitor):**
        - Keep the existing gold "Start Chat" button (current behavior).

**Files to modify:**

- [DashboardController.php](file:///home/maisam/Desktop/VisionTechChat/app/Http/Controllers/DashboardController.php) — `monitoring()` method (add eager-load)
- [monitoring.blade.php](file:///home/maisam/Desktop/VisionTechChat/resources/views/dashboard/monitoring.blade.php) — add conditional button rendering

---

### 2. Show Agent Name on Already-Joined Chats

**Current:** No indication of which agent joined a visitor's chat.

**Proposed Change:**

- Since we're already eager-loading `chats.participants` (from change #1), show the agent's name:
    ```blade
    @php $activeChat = $session->chats->first(); @endphp
    @if($activeChat)
        Joined by: {{ $activeChat->participants->first()?->active_pseudo_name ?? $activeChat->participants->first()?->name ?? 'Agent' }}
    @endif
    ```
- Display agent name with a small user icon under the "Chat Active" button area.

**Files to modify:**

- [monitoring.blade.php](file:///home/maisam/Desktop/VisionTechChat/resources/views/dashboard/monitoring.blade.php) — show agent name in card

---

### 3. Show Number of Visitors on Menu

**Current:** The nav bar shows `Visitors` as plain text with no count.

**Proposed Change:**

- In `DashboardController.monitoring()`, the count is already available (`$onlineVisitors->count()`).
- For the **layout** (all-pages nav), we need the count available globally. Use a **View Composer** or pass it from a shared middleware/service provider.
- Add a small badge/count next to "Visitors" in the nav:
    ```html
    Visitors <span class="badge">{{ $visitorCount }}</span>
    ```
- Style: Small rounded gold badge, e.g., `bg-[#D4AF37] text-black text-[10px] rounded-full px-1.5 py-0.5 ml-1`.

**Files to modify:**

- [AppServiceProvider.php](file:///home/maisam/Desktop/VisionTechChat/app/Providers/AppServiceProvider.php) — add a View Composer to share `$visitorCount`
- [dashboard.blade.php](file:///home/maisam/Desktop/VisionTechChat/resources/views/layouts/dashboard.blade.php) — add count badge next to "Visitors" nav item

---

### 4. Show Number of New Unread Messages on Visitors Page

**Current:** No unread message count shown on visitor page.

**Proposed Change:**

- In `DashboardController.monitoring()`, for each session with an active chat, include the `unread_count` field from the `chats` table.
- On each visitor card, if there are unread messages, show a small red bubble badge with the count.
- Display it near the visitor name or on the card's top-right corner:
    ```html
    @if($activeChat && $activeChat->unread_count > 0)
    <span class="bg-red-500 text-white text-[10px] rounded-full px-1.5 py-0.5">
        {{ $activeChat->unread_count }}
    </span>
    @endif
    ```

**Files to modify:**

- [monitoring.blade.php](file:///home/maisam/Desktop/VisionTechChat/resources/views/dashboard/monitoring.blade.php) — show unread badge per visitor card

---

## Chat Page Changes

### 5. Add "End Chat" Button

**Current:** The `closeChat` method and route `dashboard.chat.close` exist, but there is no button in the UI to trigger it on either `chat.blade.php` or `inbox.blade.php`.

**Proposed Change:**

- Add an "End Chat" button in the header area (next to the status badge) on **both** `chat.blade.php` and `inbox.blade.php`.
- Button design: Red-styled danger button with confirmation, only visible when chat status is `active`.
    ```html
    @if($chat->status === 'active')
    <button
        onclick="endChat()"
        class="px-3 py-1 text-xs rounded-full bg-red-500/20 text-red-400 hover:bg-red-500/30"
    >
        End Chat
    </button>
    @endif
    ```
- JavaScript `endChat()` function:
    - Show a `confirm()` dialog: "Are you sure you want to end this chat?"
    - On confirm, POST to `/dashboard/chat/{uuid}/close` with CSRF token.
    - On success, update the status badge to "Closed" and disable the message input.

**Files to modify:**

- [chat.blade.php](file:///home/maisam/Desktop/VisionTechChat/resources/views/dashboard/chat.blade.php) — add End Chat button + JS function
- [inbox.blade.php](file:///home/maisam/Desktop/VisionTechChat/resources/views/dashboard/inbox.blade.php) — add End Chat button + JS function

---

### 6. Show Number of Unread Chats on Menu

**Current:** The nav bar shows `Live Chat` as plain text with no unread count.

**Proposed Change:**

- Count chats with `unread_count > 0` for the current user's assigned clients.
- Share this count globally via the same View Composer as change #3.
- Add a red badge next to "Live Chat" in the nav:
    ```html
    Live Chat <span class="badge bg-red-500">{{ $unreadChatCount }}</span>
    ```
- Style: Red rounded badge for unread items to distinguish from visitor count.

**Files to modify:**

- [AppServiceProvider.php](file:///home/maisam/Desktop/VisionTechChat/app/Providers/AppServiceProvider.php) — add `$unreadChatCount` to the View Composer
- [dashboard.blade.php](file:///home/maisam/Desktop/VisionTechChat/resources/views/layouts/dashboard.blade.php) — add unread count badge next to "Live Chat" nav item

---

## Summary of Files to Modify

| File                      | Changes                                                                     |
| ------------------------- | --------------------------------------------------------------------------- |
| `DashboardController.php` | Eager-load chats + participants in `monitoring()`                           |
| `monitoring.blade.php`    | Differentiate joined/new chats, show agent name, show unread count per card |
| `AppServiceProvider.php`  | Add View Composer sharing `$visitorCount` and `$unreadChatCount`            |
| `dashboard.blade.php`     | Add visitor count and unread chat count badges to nav                       |
| `chat.blade.php`          | Add End Chat button + JS                                                    |
| `inbox.blade.php`         | Add End Chat button + JS                                                    |

---

## Verification Plan

### Manual Verification

Since the project has no existing automated tests covering these features, verification will be manual:

1. **Visitor Page — Joined vs New chat:**
    - Open the Visitors/Monitoring page.
    - Verify new visitors show the gold "Start Chat" button.
    - Join a chat, return to Visitors page — verify the card now shows "Continue Chat" (green) with the agent's name.

2. **Nav badge counts:**
    - Check the top nav bar — "Visitors" should show a count badge with the number of online visitors.
    - "Live Chat" should show a red badge with the number of chats with unread messages (if any).

3. **Unread messages on Visitor cards:**
    - Have a visitor send a message. Verify the unread count badge appears on their card in the Visitors page.

4. **End Chat button:**
    - Open a chat page (`chat.blade.php` or `inbox.blade.php`).
    - Verify the "End Chat" button appears next to the status badge for active chats.
    - Click it, confirm the dialog, verify the chat status changes to "Closed" and the input is disabled.
    - Verify the button is not shown for already-closed chats.

> [!IMPORTANT]
> These changes are UI/Backend only and do not affect the visitor-facing widget. Manual testing by the user on a running dev server is the recommended verification approach.
