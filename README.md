# VisionTechChat

A powerful, real-time live chat support system built with Laravel, Reverb, and Vanilla JavaScript. Designed to help agents interact with website visitors seamlessly.

## 🚀 Key Features

### For Agents (Dashboard)

-   **Real-time Inbox**: Receive messages instantly without refreshing.
-   **Live Visitor Tracking**: See who is online, their location, country, and browsing history in real-time.
-   **Proactive Messaging**: Send greetings to visitors ("Active Visitors" list) before they initiate a chat.
-   **File Sharing**: Send and receive images and documents seamlessly.
-   **Canned Responses**: Use quick shortcuts (e.g., `/hi`, `/pricing`) for common replies.
-   **Typing Indicators**: See when visitors are typing a message.
-   **Client Management**: Manage multiple websites/widgets from one dashboard.
-   **Multi-Agent Support**: Assign agents to specific clients or departments.
-   **Pseudo Names**: Agents can chat under different aliases (profiles) to protect privacy or represent different roles.

### For Visitors (Widget)

-   **Embeddable Widget**: Lightweight, standalone Vanilla JS widget (No heavy framework dependencies).
-   **Customizable Design**: Matches the client's branding (Colors, Logo, Position).
-   **Offline Forms**: Lead capture form when no agents are online.
-   **Sound & Toast Notifications**: Alerts visitors of new messages even when the chat bubble is closed.
-   **File Attachments**: Visitors can share screenshots or documents.

## 🛠️ Technology Stack

-   **Backend**: Laravel Framework (PHP 8.2+)
-   **Real-time**: Laravel Reverb (WebSockets, Pusher Protocol Compatible)
-   **Frontend (Dashboard)**: Blade Templates, TailwindCSS, Alpine.js
-   **Frontend (Widget)**: Vanilla JavaScript
-   **Database**: MySQL
-   **Storage**: Local / S3 (Configurable)

## ⚙️ Installation & Setup

### Prerequisites

-   PHP 8.2 or higher
-   Composer
-   Node.js & NPM
-   MySQL Database

### Steps

1.  **Clone the repository**

    ```bash
    git clone https://github.com/yourusername/vision-tech-chat.git
    cd vision-tech-chat
    ```

2.  **Install PHP Dependencies**

    ```bash
    composer install
    ```

3.  **Install Node Dependencies**

    ```bash
    npm install
    ```

4.  **Environment Setup**

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

    -   Configure your database credentials in `.env` (`DB_DATABASE`, `DB_USERNAME`, etc.).
    -   Ensure `BROADCAST_CONNECTION=reverb` is set.
    -   Set `FILESYSTEM_DISK=public` (or your preferred driver).

5.  **Database Migration**

    ```bash
    php artisan migrate
    ```

6.  **Build Assets**

    ```bash
    npm run build
    ```

7.  **Start WebSocket Server (Reverb)**

    ```bash
    php artisan reverb:start
    ```

    _(Keep this running in a separate terminal or service)_

8.  **Run Application**
    ```bash
    php artisan serve
    ```

## ⏰ Scheduler (Periodic Tasks)

This application uses Laravel's scheduler to perform maintenance tasks, such as marking inactive visitors as offline.

Add the following Cron entry to your server:

```bash
* * * * * cd /path/to/vision-tech-chat && php artisan schedule:run >> /dev/null 2>&1
```

**Scheduled Tasks:**

-   `visitors:cleanup-stale`: Runs every minute to update visitor online status.
-   `chat:cleanup-files`: Runs daily to remove temporary files.

## 📦 Widget Installation

To add the chat widget to an external website, include the following script in the `<body>` tag.

```html
<!-- VisionTechChat Widget -->
<script>
    (function (w, d, s, u) {
        w.VisionTechChat = {
            key: "YOUR_WIDGET_KEY",
            api_url: "http://localhost:8000", // Replace with your app URL
        };
        var h = d.getElementsByTagName(s)[0],
            j = d.createElement(s);
        j.async = true;
        j.src = u + "/widget.js";
        h.parentNode.insertBefore(j, h);
    })(window, document, "script", "http://localhost:8000");
</script>
```

-   **YOUR_WIDGET_KEY**: Found in **Admin Dashboard > Clients > Edit > Widget Code**.
-   **api_url**: The base URL where VisionTechChat is hosted.

## 🤝 Contributing

Contributions are welcome! Please submit a Pull Request or open an Issue.

## 📄 License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
