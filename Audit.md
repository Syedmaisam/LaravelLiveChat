# VisionTech LiveChat – System Overview & Real-Time Engagement Spec (v1.1)

**Date:** 2026-03-28  
**Status:** Production Ready + UX Refinement Phase  
**Stack:** Laravel + Reverb (WebSockets) + Blade UI  

---

## 1. Executive Summary

VisionTech LiveChat is a multi-tenant, real-time customer engagement platform designed for high-volume environments.

The system is technically stable and production-ready, but we are now refining **real-time UX and agent coordination** to ensure:

- Instant response to visitors
- Zero missed leads
- Smooth multi-agent experience

This platform is intended to **replace Tawk.to** for internal sales operations.

---

## 2. Production Readiness

### ✅ Status: READY FOR LAUNCH

### Required Server Configuration

#### Queue Worker (Essential)
- Required for WebSocket broadcasting performance  
- Run as daemon:

php artisan queue:work

- Recommended:

QUEUE_CONNECTION=redis


---

#### Environment Security


APP_ENV=production
APP_DEBUG=false


Run:

php artisan config:cache
php artisan route:cache


---

#### Scheduler

cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1

---

## 3. Current Features (v1.0)

### ✅ Widget Experience
- Auto-scroll with smart behavior
- Unread message indicator
- Optimistic UI (message status)
- Toast notifications
- Modern UI styling

---

### ✅ Agent Dashboard
- Real-time chat updates
- Live typing indicators
- Visitor tracking (country, device, pages)
- Multi-tenant isolation
- Basic analytics

---

### ✅ Technical Core
- Laravel Reverb (WebSockets)
- Queue-based event broadcasting
- Auto reconnect handling
- Secure authorization

---

## 4. Maintenance Commands


php artisan optimize:clear
php artisan storage:link
php artisan queue:restart
npm run build


---

# 5. 🔔 Real-Time Engagement System (Critical)

## 5.1 Purpose

Define how agents are notified and how they react to new visitors.

This system is designed to:
- Create urgency
- Ensure instant response
- Coordinate multiple agents efficiently

---

## 5.2 Core Principle

Every new visitor should feel like:

**📞 Incoming call requiring immediate attention**

---

## 5.3 Events

System relies on these real-time events:

- `visitor.created` → New visitor arrives  
- `agent.joined` → Any agent joins chat  
- `visitor.left` → Visitor leaves  

---

## 5.4 New Visitor Flow

### When `visitor.created` triggers:

### 1. Start Continuous Ringing

- Replace current short sound
- Use **looping ringing sound**
- Must continue until explicitly stopped

---

### 2. Show Green Top Banner

#### UI Requirements:

- Fixed at top
- Full width
- Green background
- High z-index

#### Text:

**New visitor waiting — Click to join**

#### Behavior:

- Entire banner clickable
- Clicking opens chat instantly

---

## 5.5 Ringing Stop Conditions

Ringing must stop ONLY when:

---

### ✅ Agent Joins

Event:

agent.joined


Behavior:
- Stop ringing for ALL agents
- Hide banner
- Mark chat as handled

---

### ❌ Visitor Leaves

Event:

visitor.left


Behavior:
- Stop ringing immediately
- Hide banner
- Mark chat inactive

---

## 5.6 Multi-Agent Coordination

### Rules:

- Multiple agents CAN join chat
- BUT:
  - Ringing stops when first agent joins
  - All agents must sync instantly

---

### Flow:

1. Visitor arrives  
2. All agents:
   - Hear ringing  
   - See banner  
3. One agent joins  
4. System broadcasts `agent.joined`  
5. All others:
   - Ringing stops  
   - Banner disappears  
   - Chat marked as active  

---

## 5.7 Banner Behavior

| Scenario | Behavior |
|--------|---------|
| New visitor | Show banner |
| Agent joins | Hide banner |
| Visitor leaves | Hide banner |

---

## 5.8 Sound System Requirements

### Current Issue:
- Sound plays once (~1 second)

---

### Required:

- Continuous loop
- Manually stoppable
- Synced across all agents

---

### Notes:

- Use looping audio
- Control via JS state
- Handle browser autoplay restrictions

---

## 5.9 State Management

System must track:


isRinging = true/false
activeVisitorId = null | id
chatClaimed = true/false


---

## 5.10 Event Flow Summary


visitor.created
→ start ringing
→ show banner

agent.joined
→ stop ringing
→ hide banner

visitor.left
→ stop ringing
→ hide banner


---

## 5.11 Edge Cases

System must handle:

- Agent reconnect
- Visitor reconnect
- Multiple visitors
- Inactive browser tabs
- Multiple tabs per agent
- Autoplay restrictions

---

## 5.12 UX Goals

System must feel:

- ⚡ Instant  
- 🔔 Attention-grabb
