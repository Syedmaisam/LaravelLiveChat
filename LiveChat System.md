## **Complete System Plan \- Custom Live Chat Platform**

### **Project Overview**

A private live chat system for a company running PPC campaigns across 20 websites (academic writing, ebooks, digital services). Complete data ownership with secure file handling and multi-agent collaboration.

---

## **PHASE 1: SYSTEM ARCHITECTURE**

### **1.1 Technology Stack**

* **Backend:** Laravel 11 \+ Laravel Reverb (WebSocket)  
* **Frontend:** Blade Templates \+ Alpine.js \+ Tailwind CSS  
* **Database:** MySQL (main) \+ Redis (sessions, broadcasting, cache)  
* **Storage:** Local server (auto-delete after 15 days)  
* **Queue:** Laravel Queue for async tasks  
* **Notifications:** Browser Push Notifications API \+ Sound alerts

### **1.2 Server Requirements**

* PHP 8.2+  
* MySQL 8.0+  
* Redis 6.0+  
* Node.js (for asset compilation)  
* Reverb Server running on port 8080  
* Storage: \~50GB for files (with auto-cleanup)

---

## **PHASE 2: DATABASE SCHEMA**

### **Core Tables:**

**1\. clients (websites)**

\- id  
\- name (e.g., "Academic Writing Site")  
\- domain (e.g., "academichelp.com")  
\- widget\_key (unique identifier)  
\- logo  
\- widget\_settings (json: colors, position, welcome text)  
\- is\_active

\- created\_at

**2\. users (agents)**

\- id  
\- name (real name)  
\- email  
\- password  
\- pseudo\_name (display name in chats)  
\- avatar (fixed avatar file)  
\- status (online/offline/away)  
\- last\_seen\_at  
\- push\_subscription (json \- for browser push)

\- created\_at

**3\. client\_agent (assignment table)**

\- id  
\- client\_id  
\- user\_id (agent)

\- assigned\_at

**4\. visitors**

\- id  
\- visitor\_key (uuid \- stored in cookie)  
\- client\_id  
\- name (from lead form)  
\- email  
\- phone  
\- ip\_address  
\- country  
\- city  
\- device (mobile/desktop/tablet)  
\- browser  
\- os  
\- first\_visit\_at  
\- last\_visit\_at  
\- total\_visits

\- created\_at

**5\. visitor\_sessions**

\- id  
\- visitor\_id  
\- client\_id  
\- session\_key (uuid)  
\- referrer\_url  
\- landing\_page  
\- current\_page  
\- is\_online (boolean)  
\- started\_at

\- last\_activity\_at

**6\. visitor\_page\_visits**

\- id  
\- visitor\_session\_id  
\- page\_url  
\- page\_title  
\- time\_spent (seconds)

\- visited\_at

**7\. chats**

\- id  
\- client\_id  
\- visitor\_id  
\- visitor\_session\_id  
\- status (waiting/active/closed)  
\- lead\_form\_filled (boolean)  
\- started\_at  
\- ended\_at

\- ended\_by (agent/visitor)

**8\. chat\_participants (for multi-agent)**

\- id  
\- chat\_id  
\- user\_id (agent)  
\- joined\_at

\- left\_at

**9\. messages**

\- id  
\- chat\_id  
\- sender\_type (agent/visitor)  
\- sender\_id (user\_id or visitor\_id)  
\- message\_type (text/file)  
\- message (text content)  
\- file\_path (if file)  
\- file\_name  
\- file\_size  
\- file\_type  
\- is\_read

\- created\_at

**10\. chat\_transfers**

\- id  
\- chat\_id  
\- from\_agent\_id  
\- to\_agent\_id  
\- transferred\_at

\- reason (optional)

**11\. notifications**

\- id  
\- user\_id (agent)  
\- type (new\_visitor/new\_message/chat\_transfer)  
\- data (json)  
\- read\_at

\- created\_at

**12\. analytics\_snapshots (daily aggregation)**

\- id  
\- date  
\- client\_id  
\- user\_id (agent, nullable)  
\- total\_visitors  
\- total\_chats  
\- avg\_response\_time  
\- total\_messages\_sent  
\- total\_files\_shared

\- created\_at

---

## **PHASE 3: CORE FEATURES BREAKDOWN**

### **3.1 Widget System**

**Widget Behavior:**

1. **Initial Load:**  
   * Check for visitor\_key in cookie  
   * If exists: Load previous chat history  
   * If not: Generate new visitor\_key  
   * Track page visit in background  
2. **Lead Form (Before Chat):**  
   * Shows immediately when visitor clicks chat icon  
   * Fields: Name\*, Email\*, Phone\*, Service Type (dropdown)  
   * "Start Chat" button after form submission  
   * Agent messages visible **during** form filling (encouraging)  
3. **Chat Interface:**  
   * WhatsApp-like design  
   * Message bubbles (visitor \= left, agent \= right)  
   * Agent pseudo name \+ avatar displayed  
   * File upload button (max 10MB)  
   * Typing indicator  
   * Online/offline status  
   * Close chat button  
   * Minimize/maximize widget  
4. **Offline Mode:**  
   * "All agents are offline" message  
   * Lead form \+ message box  
   * "We'll respond via email" text  
   * Still stores in system for agents to see later

**Widget Technical:**

* Lightweight JS snippet (\~50KB minified)  
* Lazy loads chat interface  
* Cookie-based visitor tracking (365 days)  
* WebSocket connection for real-time  
* Fallback to polling if WebSocket fails

---

### **3.2 Agent Dashboard (WhatsApp Web Style)**

**Layout Structure:**

┌─────────────────────────────────────────────────────────┐  
│ Header: Logo | Notifications Bell | Agent Name | Status │  
├──────────────┬──────────────────────────────────────────┤  
│              │                                          │  
│  Left Panel  │        Main Chat Area                   │  
│  (Chats)     │                                          │  
│              │                                          │  
│  \- Waiting   │  ┌────────────────────────────────────┐ │  
│  \- Active    │  │ Visitor Header (name, page, etc)  │ │  
│  \- Closed    │  ├────────────────────────────────────┤ │  
│              │  │                                    │ │  
│  Filters:    │  │     Messages Area                 │ │  
│  \- By Client │  │     (scrollable)                  │ │  
│  \- By Agent  │  │                                    │ │  
│              │  │                                    │ │  
│              │  ├────────────────────────────────────┤ │  
│  \[+ New\]     │  │ Input Box \+ File Upload \+ Send    │ │  
│              │  └────────────────────────────────────┘ │  
│              │                                          │  
├──────────────┴──────────────────────────────────────────┤  
│  Right Sidebar: Visitor Details (collapsible)          │  
│  \- Location, Device, Browser                            │  
│  \- Pages visited timeline                               │  
│  \- Files shared                                         │  
│  \- Previous chats                                       │  
│  \- Transfer button, Close button                        │

└─────────────────────────────────────────────────────────┘

**Left Panel \- Chat List:**

* **Waiting Chats** (Red badge, new visitors)  
  * Show: visitor name, client name, time waiting  
  * Ring sound \+ desktop notification on new  
  * Sorted by: oldest waiting first  
* **Active Chats** (Green badge)  
  * Show: visitor name, last message preview, unread count  
  * Multiple tabs like WhatsApp  
  * Can switch between chats instantly  
  * Sorted by: last activity  
* **Closed Chats**  
  * Archived chats  
  * Searchable history

**Filters:**

* Dropdown: "All Clients" or select specific client  
* Dropdown: "All Agents" or select specific agent  
* Quick filters: "My Chats", "Unassigned", "Waiting"

**Chat Opening:**

* Click on waiting chat → Auto-joins with pseudo name  
* "Join Chat" button appears  
* Other assigned agents can also join (collaborative)

---

### **3.3 Real-Time Notifications**

**Notification Types:**

1. **New Visitor Alert (Ring)**  
   * Triggers when: New visitor opens chat widget  
   * Who receives: All agents assigned to that client  
   * Contains:  
     * Sound alert (ring tone)  
     * Desktop push notification  
     * Red badge on dashboard  
     * Visitor details popup  
2. **New Message**  
   * From visitor in active chat  
   * Sound notification (message tone)  
   * Badge counter update  
   * Desktop notification if tab inactive  
3. **Chat Transfer**  
   * When another agent assigns you  
   * Desktop notification  
   * Chat appears in your active list  
4. **Visitor Page Change**  
   * Silent update in visitor info panel  
   * Shows current page URL in real-time  
5. **Visitor Goes Offline**  
   * Icon changes in chat header  
   * "Visitor is offline" indicator

**Push Notification Setup:**

* Request permission on agent login  
* Store subscription in database  
* Use Laravel Broadcasting \+ Reverb  
* Fallback: In-app notifications if permission denied

---

### **3.4 Multi-Agent Collaboration (Group Chat Style)**

**How it works:**

1. **Agent A joins a waiting chat:**  
   * Chat status changes: waiting → active  
   * Agent A listed in chat\_participants  
   * Visitor sees: "Agent A joined the chat"  
2. **Agent B (also assigned to same client) wants to join:**  
   * Clicks on same chat in their dashboard  
   * System allows join  
   * Agent B listed in chat\_participants  
   * Visitor sees: "Agent B joined the chat"  
   * Both agents see all messages in real-time  
3. **Messages:**  
   * Each message shows sender's pseudo name  
   * Color coding: Agent A (blue), Agent B (green), Visitor (gray)  
   * All agents see who sent what  
4. **Internal Notes (optional feature):**  
   * Agents can send notes to each other (not visible to visitor)  
   * Use tag: `@AgentName message` for private notes

**Use Case:**

* Agent A handling complex query  
* Agent B (specialist) joins to help  
* Both collaborate to resolve visitor issue  
* Visitor feels well-supported

---

### **3.5 File Upload & Management**

**Upload Flow:**

**Visitor Side:**

1. Clicks paperclip icon in widget  
2. File picker opens (PDF, DOC, DOCX, JPG, PNG, JPEG)  
3. Client-side validation: size \< 10MB, allowed types  
4. Upload with progress bar  
5. File appears as attachment bubble in chat

**Agent Side:**

1. Same file picker in dashboard  
2. Can send files to visitor  
3. Preview option before sending

**Backend Processing:**

1\. File uploaded to: storage/app/chat-files/{chat\_id}/  
2\. Generate unique filename: {timestamp}\_{original\_name}  
3\. Store in messages table with metadata  
4\. Return download URL to both parties

5\. Virus scan (optional \- ClamAV)

**Auto-Delete System:**

\- Daily cron job (runs at 3 AM)  
\- Finds files older than 15 days  
\- Deletes from storage  
\- Updates message record: file\_path \= null, deleted\_at \= now()

\- Visitor/Agent sees: "File expired" instead of download link

**Security:**

* Private storage folder (not public)  
* Download via authenticated route with token  
* Check permissions: only chat participants can download  
* Content-Type validation (prevent executable uploads)  
* Rate limiting on uploads

---

### **3.6 Visitor Tracking & Monitoring**

**Real-Time Tracking:**

**What we track:**

1. **Session Start:**  
   * Landing page  
   * Referrer (Google Ads, Facebook, Direct, etc.)  
   * UTM parameters (campaign tracking)  
   * Device, Browser, OS  
   * IP → Location (Country, City)  
2. **Navigation:**  
   * Every page change tracked  
   * Time spent on each page  
   * Scroll depth (optional)  
   * Current page highlighted in dashboard  
3. **Behavior:**  
   * Form interactions (which forms viewed)  
   * CTA clicks  
   * Downloads  
   * Add to cart (if ecommerce)

**Dashboard Display:**

**Visitor Info Panel (Right Sidebar):**

┌─────────────────────────────────┐  
│ Visitor: John Doe              │  
│ 📧 john@example.com            │  
│ 📱 \+1234567890                 │  
├─────────────────────────────────┤  
│ 🌍 New York, USA               │  
│ 💻 Chrome on Windows           │  
│ 📶 Online (green dot)          │  
├─────────────────────────────────┤  
│ Current Page:                   │  
│ → /pricing                     │  
│   (Live update)                 │  
├─────────────────────────────────┤  
│ Page History: (Timeline)        │  
│ • /pricing (2 min ago)         │  
│ • /services (5 min ago)        │  
│ • /home (8 min ago)            │  
├─────────────────────────────────┤  
│ Previous Chats: 2              │  
│ • Chat 1 \- 3 days ago          │  
│   "Asked about pricing"         │  
│ • Chat 2 \- 1 week ago          │  
│   "Service inquiry"             │  
├─────────────────────────────────┤  
│ Files Shared: 1                 │  
│ • proposal.pdf (Download)      │  
├─────────────────────────────────┤  
│ \[Transfer Chat\] \[End Chat\]     │

└─────────────────────────────────┘

**Live Visitor Monitoring Page:**

* Separate dashboard view  
* Shows ALL visitors currently on any client website  
* Grid/List view  
* Real-time page updates  
* "Start Chat" button (proactive engagement)

---

### **3.7 Chat Transfer Feature**

**Flow:**

1. **Agent A (in active chat) clicks "Transfer":**  
   * Modal opens  
   * Shows list of other agents assigned to this client  
   * Agent status shown (online/offline/busy)  
   * Optional: Add transfer reason/note  
2. **Agent A selects Agent B:**  
   * System sends notification to Agent B  
   * Chat appears in Agent B's dashboard  
   * Agent B clicks to join  
   * Visitor sees: "You've been transferred to Agent B"  
3. **Options:**  
   * **Transfer & Leave:** Agent A exits, only Agent B handles  
   * **Transfer & Stay:** Both remain in chat (collaboration)  
4. **History:**  
   * All transfers logged in chat\_transfers table  
   * Visible in chat history: "Transferred from A to B at 2:30 PM"

---

### **3.8 Search & Filter System**

**Global Search (Top bar):**

* Search by: Visitor name, email, phone, message content  
* Real-time results as you type  
* Shows: Chat preview, date, client name  
* Click to open chat

**Filters on Chat List:**

**By Status:**

* Waiting (new)  
* Active (ongoing)  
* Closed (ended)

**By Client:**

* Dropdown with all 20 clients  
* Multi-select option

**By Date Range:**

* Today, Yesterday, Last 7 days, Last 30 days, Custom range  
* Date picker

**By Agent:**

* My chats only  
* Specific agent  
* Unassigned chats

**By Lead Status:**

* Lead form filled  
* Lead form pending  
* Offline messages

**Export:**

* Export filtered results to CSV  
* Include: visitor details, messages, timestamps  
* One-click "Export to Excel" button

---

### **3.9 Analytics & Reporting**

**Dashboard Metrics (Overview Page):**

┌─────────────────────────────────────────────────────────┐  
│                    Today's Stats                        │  
├──────────┬──────────┬──────────┬──────────┬────────────┤  
│ Visitors │ Chats    │ Avg Resp │ Messages │ Conversions│  
│   143    │   87     │   45s    │   523    │    12      │  
└──────────┴──────────┴──────────┴──────────┴────────────┘

┌─────────────────────────────────────────────────────────┐  
│              Chats Over Time (Chart)                    │  
│  (Line graph: Last 30 days)                             │  
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐  
│         Performance by Client (Table)                   │  
│  Client Name    | Visitors | Chats | Conversion Rate   │  
│  Academic Site  |   45     |  28   |    62%           │  
│  Ebook Store    |   38     |  22   |    58%           │  
│  ...                                                     │  
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐  
│         Agent Performance (Table)                       │  
│  Agent Name     | Chats | Avg Time | Satisfaction     │  
│  Agent A        |  32   |   3m 20s |    4.8/5        │  
│  Agent B        |  28   |   4m 10s |    4.6/5        │  
│  ...                                                     │  
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐  
│         Busiest Hours (Heatmap)                         │  
│  Shows: Peak chat times by hour and day                │

└─────────────────────────────────────────────────────────┘

**Reports Available:**

1. **Chat Volume Report:**  
   * Total chats per day/week/month  
   * By client, by agent  
   * Export to PDF/Excel  
2. **Response Time Report:**  
   * Average first response time  
   * Average resolution time  
   * By agent comparison  
3. **Conversion Report:**  
   * Lead form fill rate  
   * Chat-to-lead conversion  
   * By traffic source (referrer)  
4. **File Sharing Report:**  
   * Total files shared  
   * File types breakdown  
   * Storage usage  
5. **Visitor Behavior Report:**  
   * Most visited pages before chat  
   * Average time on site  
   * Returning vs new visitors

**Date Range Selector:**

* Today, Yesterday, Last 7/30/90 days  
* Custom date range picker  
* Compare with previous period

---

### **3.10 Mobile Responsiveness**

**Agent Dashboard on Mobile:**

**Layout Adaptation:**

* **Single column layout** (no sidebars visible by default)  
* **Bottom navigation:**  
  * Chats icon  
  * Visitors icon  
  * Analytics icon  
  * Profile icon  
* **Chat list as primary view**  
* **Tap chat → Full screen chat interface**  
* **Swipe left on chat → Quick actions** (transfer, close)  
* **Floating "New Message" indicator**

**Widget on Mobile:**

* **Bottom-right floating button** (smaller)  
* **Full screen when opened**  
* **Optimized touch targets** (bigger buttons)  
* **Easy file upload** (camera access on mobile)

**Touch Optimizations:**

* Large tap targets (min 44px)  
* Swipe gestures (swipe to delete message)  
* Pull to refresh  
* Haptic feedback

---

## **PHASE 4: DEVELOPMENT ROADMAP**

### **Sprint 1 (Week 1-2): Foundation**

✅ Laravel setup \+ Reverb installation  
 ✅ Database schema creation  
 ✅ Authentication system  
 ✅ Basic agent dashboard layout (Blade \+ Alpine.js)  
 ✅ Client (website) management CRUD  
 ✅ Agent management with pseudo names  
 ✅ Agent-Client assignment system

### **Sprint 2 (Week 3-4): Widget & Tracking**

✅ Embeddable widget JavaScript  
 ✅ Widget UI (chat interface)  
 ✅ Lead form in widget  
 ✅ Visitor tracking system (cookies, sessions)  
 ✅ Page visit tracking  
 ✅ Location detection (IP-based)

### **Sprint 3 (Week 5-6): Real-Time Chat**

✅ Reverb WebSocket setup  
 ✅ Chat creation & management  
 ✅ Message sending/receiving (agent ↔ visitor)  
 ✅ Typing indicators  
 ✅ Multi-agent chat support (group chat)  
 ✅ Online/offline status

### **Sprint 4 (Week 7-8): Notifications & Alerts**

✅ Browser push notification setup  
 ✅ Sound alerts (ring for new visitor)  
 ✅ Desktop notifications  
 ✅ Real-time badge counters  
 ✅ Notification center in dashboard

### **Sprint 5 (Week 9-10): File Upload**

✅ File upload in widget  
 ✅ File upload in dashboard  
 ✅ File storage system  
 ✅ File download with authentication  
 ✅ Auto-delete cron job (15 days)  
 ✅ File type & size validation

### **Sprint 6 (Week 11-12): Advanced Features**

✅ Chat transfer system  
 ✅ Search & filter functionality  
 ✅ Visitor detail panel (right sidebar)  
 ✅ Chat history for returning visitors  
 ✅ Offline message handling  
 ✅ Close chat functionality

### **Sprint 7 (Week 13-14): Monitoring & Analytics**

✅ Live visitor monitoring page  
 ✅ Real-time page tracking display  
 ✅ Analytics dashboard  
 ✅ Charts & graphs (using Chart.js)  
 ✅ Reports generation  
 ✅ Export to CSV/Excel

### **Sprint 8 (Week 15-16): Polish & Mobile**

✅ Mobile responsive dashboard  
 ✅ Mobile responsive widget  
 ✅ Performance optimization  
 ✅ Security hardening  
 ✅ Testing (unit \+ integration)  
 ✅ Bug fixes

### **Sprint 9 (Week 17-18): Deployment & Training**

✅ Server setup & deployment  
 ✅ SSL certificate  
 ✅ Backup system  
 ✅ Monitoring tools (logs, errors)  
 ✅ Agent training materials  
 ✅ Admin documentation

---

## **PHASE 5: TECHNICAL SPECIFICATIONS**

### **5.1 Real-Time Architecture**

**Laravel Reverb Setup:**

env  
REVERB\_APP\_ID=your-app-id  
REVERB\_APP\_KEY=your-app-key  
REVERB\_APP\_SECRET=your-secret  
REVERB\_HOST=0.0.0.0  
REVERB\_PORT=8080  
REVERB\_SCHEME=https  
\`\`\`

\*\*Broadcasting Channels:\*\*  
\`\`\`  
\- visitors.{client\_id} → New visitor alerts  
\- chat.{chat\_id} → Chat messages  
\- agent.{user\_id} → Personal notifications

\- monitoring → Live visitor tracking updates

**Events to Broadcast:**

* NewVisitorJoined  
* MessageSent  
* AgentTyping  
* VisitorTyping  
* VisitorPageChanged  
* AgentJoinedChat  
* AgentLeftChat  
* ChatTransferred  
* ChatClosed  
* FileUploaded

---

### **5.2 Security Measures**

**Authentication:**

* Laravel Sanctum for API  
* Session-based for dashboard  
* CSRF protection on all forms  
* Password hashing (bcrypt)

**File Upload Security:**

* Whitelist allowed MIME types  
* Filename sanitization  
* Random filename generation  
* Storage outside public directory  
* Token-based download URLs  
* Rate limiting (max 5 files/minute)

**XSS Prevention:**

* Blade escaping by default  
* HTML purifier for message content  
* Content Security Policy headers

**Rate Limiting:**

* Widget API: 100 requests/minute per visitor  
* Message sending: 10 messages/minute per user  
* File upload: 5 uploads/minute

**Data Privacy:**

* No third-party analytics  
* Encrypted database backups  
* Secure WebSocket (WSS)  
* GDPR-ready (data export/delete)

---

### **5.3 Performance Optimization**

**Database:**

* Indexes on frequently queried columns  
* Pagination on chat lists (50 per page)  
* Eager loading to prevent N+1 queries  
* Database caching (Redis) for visitor counts

**File Storage:**

* Symlink storage for quick access  
* CDN option for static assets  
* Lazy loading chat history  
* Image compression on upload

**Frontend:**

* Asset minification  
* Lazy load Alpine.js components  
* Virtual scrolling for long chat lists  
* Debounced search inputs  
* Service Worker for offline support

**Caching Strategy:**

* Cache analytics queries (1 hour)  
* Cache visitor counts (5 minutes)  
* Cache agent availability (real-time)  
* Session store in Redis

---

### **5.4 Monitoring & Maintenance**

**Health Checks:**

* Reverb server status  
* Database connection  
* Redis connection  
* Disk space (file storage)  
* Queue worker status

**Logging:**

* Laravel log (errors, warnings)  
* Chat activity log (audit trail)  
* File upload/download log  
* Authentication attempts  
* WebSocket connection issues

**Backup Strategy:**

* Daily database backup (retained 30 days)  
* Weekly full backup (retained 90 days)  
* Chat files backed up before auto-delete  
* Backup to external storage (AWS S3 or local NAS)

**Alerts:**

* Email alert when Reverb goes down  
* Email alert when disk space \< 10GB  
* Email alert on failed backups  
* Slack notification on critical errors

---

## **PHASE 6: USER ROLES & PERMISSIONS**

### **Role Structure:**

**1\. Super Admin**

* Full system access  
* Add/edit/delete clients  
* Add/edit/delete agents  
* Assign agents to clients  
* View all analytics  
* System settings

**2\. Agent**

* View assigned clients only  
* Join chats for assigned clients  
* Send/receive messages  
* Upload files  
* Transfer chats  
* View own analytics  
* Update own profile & pseudo name

**3\. Manager (Optional)**

* View all clients  
* View all chats (read-only)  
* View all analytics  
* Cannot chat directly  
* Generate reports

---

## **PHASE 7: DEPLOYMENT CHECKLIST**

**Pre-Deployment:**

* Code review completed  
* All tests passing  
* Database migrations ready  
* Environment variables configured  
* SSL certificate installed  
* Domain configured  
* Email service configured (for notifications)

**Server Setup:**

* PHP 8.2+ installed  
* MySQL 8.0+ installed  
* Redis installed & running  
* Composer dependencies installed  
* Node modules installed & built  
* Storage permissions set  
* Queue worker configured (systemd/supervisor)  
* Reverb server running (systemd/supervisor)  
* Cron jobs configured

**Post-Deployment:**

* Smoke testing (login, chat, upload)  
* Load testing (simulate 50 concurrent users)  
* Backup verification  
* Monitoring tools active  
* Agent training completed  
* Documentation delivered

---

## **QUESTIONS RESOLVED SUMMARY**

✅ **Notification System:** Ring sound \+ desktop push for assigned agents  
 ✅ **Lead Form:** Name, Email, Phone (required before visitor can send messages)  
 ✅ **Agent Assignment:** Multiple agents can join same chat (group chat style)  
 ✅ **File Upload:** Both can upload, PDF/DOC/JPG/PNG, \<10MB, local storage, auto-delete 15 days  
 ✅ **Website Management:** Added as clients, assigned to specific agents  
 ✅ **Visitor Records:** All details stored (location, IP, device, pages, files)  
 ✅ **Filters:** By client, agent, date, status, with export option  
 ✅ **Multi-Chat:** WhatsApp-style tabs, agent handles multiple chats  
 ✅ **Agent UI:** Fixed avatar, pseudo name, online/offline status  
 ✅ **Chat Transfer:** Assign other agent, they join with pseudo name  
 ✅ **Offline Mode:** Lead form \+ message, agents see when back online  
 ✅ **Chat Closing:** Both can close, most convert to WhatsApp  
 ✅ **Returning Visitors:** Cookie-based recognition, continue previous chat  
 ✅ **Analytics:** Complete monitoring & reports as discussed  
 ✅ **Mobile:** Fully responsive for agents on phone

