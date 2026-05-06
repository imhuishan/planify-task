# Tasker. — Task Management System

A lightweight, team-oriented task management web application built with PHP, MySQL, and vanilla CSS. Features a dark glassmorphism UI with full mobile responsiveness.

## Live Demo

You can access the live, deployed version of Tasker here:
**[https://planify.infinityfreeapp.com/](https://planify.infinityfreeapp.com/)**

> **Note:** To test the application, simply click **"Create one for free"** on the login page to register a new account.


## Features

- **Dashboard** — Overview of tasks assigned to you and tasks you reported, with status/priority stats and a live activity feed
- **Calendar View** — Visualise tasks on a monthly/weekly calendar (FullCalendar), drag-and-drop to reschedule, click a date to create a task
- **Projects** — Organise tasks under projects, manage team members and project leaders
- **Task Management** — Create, edit, delete tasks with priority levels (Urgent / High / Medium / Low), categories, due dates, and comments
- **Role-Based Access** — Three roles: `admin`, `manager`, `member`. Admins and managers can view all users' calendars
- **Mobile Responsive** — Collapsible sidebar, stacked layouts, and optimised views for phones and tablets

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8+ |
| Database | MySQL |
| Frontend | HTML, Vanilla CSS, JavaScript |
| Calendar | [FullCalendar v6](https://fullcalendar.io/) |
| Charts | [Chart.js](https://www.chartjs.org/) |
| Fonts | Inter (Google Fonts) |

## Project Structure

```
task-manager/
├── index.php               # Entry point (Redirects to Login)
├── config/
│   └── db.php              # Database connection & activity logger
├── public/
│   ├── index.php           # Dashboard
│   ├── calendar.php        # Calendar view
│   ├── projects.php        # Projects list
│   ├── project_details.php # Single project view
│   ├── css/
│   │   └── style.css       # All styles + responsive breakpoints
│   └── api/
│       ├── tasks.php       # Task CRUD API
│       ├── projects.php    # Project CRUD API
│       ├── comments.php    # Comments API
│       └── activities.php  # Activity feed API
├── src/
│   └── Auth/
│       ├── login.php
│       ├── register.php
│       └── logout.php
├── templates/
│   └── sidebar.php         # Shared sidebar + mobile toggle
└── database.sql            # Database schema
```

## Getting Started

### Requirements

- PHP 8.0+
- MySQL 5.7+ / MariaDB
- A web server (Apache, Nginx, or PHP built-in server)

### Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/imhuishan/planify-task.git
   cd planify-task
   ```

2. **Create and import the database**
   ```bash
   mysql -u root -p -e "CREATE DATABASE planify_task;"
   mysql -u root -p planify_task < database.sql
   ```

3. **Configure the database connection**

   Edit `config/db.php` and update your credentials:
   ```php
   $server_name = "localhost";
   $username    = "root";
   $password    = "your_password";
   $dbname      = "planify_task";
   $port        = "3306";
   ```

4. **Run the development server**
   ```bash
   php -S localhost:8000
   ```

5. **Open in your browser**

   Visit `http://localhost:8000` — you'll be redirected to the login page.

## User Roles

| Role | Permissions |
|---|---|
| `member` | View and manage own tasks |
| `manager` | View all users' calendars, manage tasks |
| `admin` | Full access — manage projects, members, and all tasks |

> Roles are set directly in the `users` table in the database.