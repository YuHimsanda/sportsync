# SportSync

**Grow your game. Manage your academy.**

SportSync is a robust PHP and MySQL-based sports-academy management system designed to bring players, coaches, referees, and academy administrators together. It provides a centralized platform to organize teams, schedule matches, track player performance, and manage academy operations securely.

## 🚀 Features

*   **Multi-Role Access Control:** Secure portals for Admins, Coaches, Referees, and Athletes/Players.
*   **Academy Management:** Register academies, manage venues, and approve member join requests.
*   **Team Organization:** Create teams, assign coaches, and manage player rosters. 
*   **Match Scheduling:** Schedule official, friendly, or practice matches with conflict detection for venues and participants.
*   **Performance Tracking:** Referees and coaches can submit match results and sport-specific player statistics (e.g., runs for Cricket, goals for Football).
*   **Internal Messaging:** Direct messaging and academy-wide announcements.
*   **Public Discovery:** Browse active academies, teams, public player profiles, and recent match results.

## 🛠️ Tech Stack

*   **Backend:** PHP (Vanilla, Session-based authentication)
*   **Database:** MySQL / MariaDB (Prepared statements via `mysqli` / `PDO`)
*   **Frontend:** HTML5, CSS3 (Custom styling with variables and responsive media queries)
*   **JavaScript:** Vanilla JS (DOM manipulation, form confirmation, password toggles)

## 📁 Folder Structure

```text
├── config/                  # Database configuration
├── includes/                # Shared header, footer, and helper functions
├── assets/
│   ├── css/style.css        # Application styles
│   └── js/app.js            # Client-side interactions
├── Media/                   # Brand assets (logo, favicons)
├── uploads/                 # User-uploaded profile pictures (generated)
├── sportsync.sql            # Database schema and seed data
└── *.php                    # Application pages (index, dashboard, profile, etc.)
```

## 🏁 Getting Started

To get a local copy up and running, follow these simple steps.

### Prerequisites

*   A local server environment like **XAMPP**, **WAMP**, or **MAMP**.
*   PHP 8.0 or higher recommended.
*   MySQL or MariaDB.

### Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/yourusername/sportsync.git
   ```
2. **Move to web root:**
   Place the `sportsync` folder in your local server's document root (e.g., `htdocs` for XAMPP).
3. **Database Setup:**
   * Open phpMyAdmin (or your preferred SQL tool).
   * Create a new database (the default is `sportsync_db`).
   * Import the `sportsync.sql` file to create the tables and initial sport categories.
4. **Configuration:**
   * If your database credentials differ from the defaults (User: `root`, Password: `[empty]`), update the connection string in your `config/config.php` (or wherever your `$conn` is initialized).
5. **Run the Application:**
   * Open your browser and navigate to `http://localhost/sportsync`
   * Register a new academy to create your first Admin account!

## 🛡️ Security

This project implements fundamental security practices including:
*   Password hashing using `password_hash()` and `password_verify()`.
*   SQL Injection prevention using prepared statements (`bind_param`).
*   Cross-Site Scripting (XSS) mitigation via strict output escaping (`htmlspecialchars`).
*   Role-based access checks (`requireRole()`) on protected routes.