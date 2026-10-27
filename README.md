# SportSync

**Grow your game. Manage your academy.**

SportSync is a PHP and MySQL web application for managing sports academies. It brings admins, coaches, referees and athletes onto one platform to organise teams, schedule matches, record results and player statistics, and communicate within the academy.

🔗 **Live demo:** [https://sportsync.alwaysdata.net](https://sportsync.alwaysdata.net)

---

## Table of Contents

- [Features](#-features)
- [User Roles](#-user-roles)
- [Tech Stack](#-tech-stack)
- [Project Structure](#-project-structure)
- [Database](#-database)
- [Getting Started](#-getting-started)
- [Security](#-security)
- [Documentation](#-documentation)
- [Author](#-author)

---

## 🚀 Features

- **Academy management** – Register an academy (the registrant becomes its admin), add venues, choose the sports it offers, and approve or reject membership requests.
- **Multi-role access control** – Separate permissions for Admins, Coaches, Referees and Athletes.
- **Team organisation** – Create teams per sport, assign coaches, captains and vice-captains, and manage rosters and team join requests.
- **Match scheduling** – Schedule official, friendly or practice matches with venue and time-conflict checks.
- **Results and player statistics** – Submit match results (scores, status) and sport-specific player stats.
- **Internal messaging** – Direct messages and academy-wide announcements, with an unread counter in the navigation.
- **Public discovery** – The home page shows public players, teams and upcoming matches without requiring login.
- **Profile management** – Update personal details, change password and upload a profile photo.
- **Admin tools** – Manage, edit and delete users, and handle approval queues.

### Supported sports and tracked statistics

| Sport | Example statistics |
|---|---|
| Cricket | Runs, balls faced, fours, sixes, overs, maidens, wickets, catches, run outs |
| Football | Goals, assists, shots on target, tackles, saves, yellow/red cards |
| Basketball | Points, 3-pointers, free throws, assists, rebounds, steals, blocks |
| Rugby | Tries, conversions, penalties, drop goals |
| Hockey | Seeded as a sport in the database |

## 👥 User Roles

| Role | What they can do |
|---|---|
| **Admin** | Register the academy, manage venues, approve members, manage users, create teams and matches, send messages |
| **Coach** | Create teams and matches, send messages to the academy |
| **Referee** | Officiate matches and submit results and stats |
| **Athlete** | View teams and matches, manage own profile, receive messages |

New members use **Join Academy** and stay *pending* until an admin approves them.

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP (vanilla, server-rendered pages, session-based authentication) |
| Database | MySQL / MariaDB, accessed through `mysqli` with prepared statements |
| Frontend | HTML5, custom CSS (variables, responsive media queries) |
| Scripting | Vanilla JavaScript (form confirmations, password visibility toggle) |
| Hosting | alwaysdata (live demo) |

No frameworks and no build step are required.

## 📁 Project Structure

```text
sportsync/
├── config/
│   └── config.php            # Database connection, session start, app constants
├── includes/
│   ├── header.php            # Shared header and navigation
│   ├── footer.php            # Shared footer
│   └── functions.php         # Helpers: auth, roles, flash messages, validation
├── assets/
│   ├── css/style.css         # Application styles
│   └── js/app.js             # Client-side interactions
├── Media/                    # Logo and favicon
├── uploads/                  # Uploaded profile photos
├── sportsync.sql             # Database schema + seed sports
├── database-schema.mmd       # ER diagram (Mermaid)
├── activity-diagram.mmd      # Activity diagram (Mermaid)
├── PROJECT-DOCUMENTATION.md  # Detailed page-by-page documentation
└── *.php                     # Application pages
```

### Main pages

| Area | Pages |
|---|---|
| Public / Auth | `index.php`, `login.php`, `logout.php`, `register-academy.php`, `join-academy.php` |
| Dashboard / Profile | `dashboard.php`, `profile.php` |
| Teams and players | `teams.php`, `team.php`, `create-team.php`, `players.php`, `player.php` |
| Matches | `matches.php`, `match.php`, `create-match.php`, `submit-result.php` |
| Admin | `approvals.php`, `manage-users.php`, `edit-user.php`, `delete-user.php`, `add-venue.php` |
| Messaging | `messages.php`, `send-message.php` |

## 🗄️ Database

The schema in `sportsync.sql` creates the `sportsync_db` database with 15 tables:

`academies`, `sports`, `academy_sports`, `users`, `user_sports`, `venues`, `teams`, `team_players`, `events`, `event_teams`, `approvals`, `team_join_requests`, `inter_academy_event_requests`, `messages`, `player_stats`.

The full ER diagram is in [`database-schema.mmd`](database-schema.mmd). You can paste it into the [Mermaid Live Editor](https://mermaid.live) to view it.

## 🏁 Getting Started

### Prerequisites

- A local server stack such as **XAMPP**, **WAMP** or **MAMP**
- **PHP 8.0+**
- **MySQL 8+** or **MariaDB**

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/YuHimsanda/sportsync.git
   ```
2. **Place it in your web root**
   Move the `sportsync` folder into `htdocs` (XAMPP) or your server's document root.
3. **Create the database**
   - Open phpMyAdmin and import `sportsync.sql`, or run:
     ```bash
     mysql -u root -p < sportsync.sql
     ```
   - This creates `sportsync_db` and seeds the sports list.
4. **Configure the connection**
   Edit `config/config.php` if your credentials differ from the defaults:
   ```php
   $host     = "127.0.0.1";
   $database = "sportsync_db";
   $username = "root";
   $password = "";
   ```
5. **Make `uploads/` writable** so profile photos can be saved.
6. **Run the app**
   Open `http://localhost/sportsync` and use **Register Academy** to create your academy and its first admin account.

## 🛡️ Security

- Passwords hashed with `password_hash()` and verified with `password_verify()`
- SQL injection prevention through prepared statements
- XSS mitigation by escaping output with `htmlspecialchars()` (via the `e()` helper)
- Role-based route protection with `requireLogin()` and `requireRole()`
- Account approval workflow: new members cannot log in until approved

## 📚 Documentation

- [`PROJECT-DOCUMENTATION.md`](PROJECT-DOCUMENTATION.md) – detailed explanation of each file and page
- [`database-schema.mmd`](database-schema.mmd) – entity relationship diagram
- [`activity-diagram.mmd`](activity-diagram.mmd) – application flow

## 👤 Authors

**Yuhas** – Computer Engineering undergraduate, KDU
GitHub: [YuHimsanda](https://github.com/YuHimsanda)

**Prabhavi** – Computer Science undergraduate, KDU
GitHub: [PrabhaviAbeysiriwardhana](https://github.com/PrabhaviAbeysiriwardhana)

**Sharuny** – Software Engineering undergraduate, KDU
GitHub: [sharuny2005](https://github.com/sharuny2005)

**Bhavika** – Software Engineering undergraduate, KDU


---

*Built as a university project to demonstrate PHP, MySQL, CRUD operations and role-based web application design.*
