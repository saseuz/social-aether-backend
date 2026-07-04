# 🌌 Aether Social Backend

[![PHP Version](https://img.shields.io/badge/PHP-8.3%2B-blue.svg)](https://php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-%5E13.8-FF2D20.svg?logo=laravel)](https://laravel.com)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

> A robust, Laravel-based API server powering a modern social experience. Aether provides everything needed for a full-featured social network, including token-based authentication, threaded comments, social graphs, and real-time trending discovery.

---

## 📖 Table of Contents

- [Tech Stack](#-tech-stack)
- [Core Features](#-core-features)
- [Prerequisites](#-prerequisites)
- [Getting Started](#-getting-started)
  - [Quick Setup](#quick-setup)
  - [Environment Configuration](#environment-configuration)
  - [Running Locally](#running-locally)
- [API Reference](#-api-reference)
  - [Example Request](#example-request)
- [Database Architecture](#-database-architecture)
- [Development Commands](#-development-commands)
- [License](#-license)

---

## 🛠 Tech Stack

- **Core:** PHP ^8.3, Laravel Framework ^13.8
- **Authentication:** Laravel Sanctum (Token-based API auth)
- **Admin Interface:** Filament (Ready for admin panel support)
- **Frontend Tooling:** Vite + Tailwind CSS (Asset processing)
- **Testing:** PHPUnit (Automated testing)

---

## ✨ Core Features

- **🔐 Authentication:** User registration, login, profile management, and secure password changes.
- **👥 Social Graph:** Follow/unfollow mechanics and intelligent user follow suggestions.
- **📰 Feed & Post Engine:** Fetch comprehensive feeds featuring nested reposts, threaded comments, likes, and bookmark states. Create, like, bookmark, and repost content.
- **💬 Threaded Comments:** Robust commenting system supporting multi-level nested replies.
- **🔔 Notifications:** Comprehensive event tracking (likes, comments, replies, follows, and reposts). Fetch, create, mark as read, or clear notifications.
- **📈 Trends Engine:** Automatic detection and categorization of trending hashtags across network posts.

---

## ⚙️ Prerequisites

Ensure your local development environment meets the following requirements:

- **PHP:** 8.3 or higher
- **Composer:** Dependency manager for PHP
- **Node.js & npm:** Node 18+ recommended
- **Database:** SQLite, MySQL, or PostgreSQL

---

## 🚀 Getting Started

### Quick Setup

For a streamlined local development setup, you can use our single-command installation script:

```bash
composer run setup
```

**Alternatively, to set up manually step-by-step:**

```bash
cd /home/zypp/code/aether-social-workspace/social-app-backend
composer install
npm install

cp .env.example .env
php artisan key:generate

# Note: If using SQLite, create the file first: touch database/database.sqlite
php artisan migrate
npm run build
```

### Environment Configuration

Update your `.env` file to match your environment variables. Here is a recommended local configuration:

```env
APP_NAME="Aether Social"
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

# Database setup (SQLite example)
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database/database.sqlite

# API & Session Drivers
SANCTUM_STATEFUL_DOMAINS=127.0.0.1
SESSION_DRIVER=file
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
```

### Running Locally

To start the backend, Vite dev server, queue listener, and log watcher simultaneously:

```bash
composer run dev
```

If you only need to boot up the backend API:

```bash
php artisan serve
```

---

## 📡 API Reference

*Note: All endpoints are prefixed with `/api`.*

### 🌍 Public Routes

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/auth/register` | Register a new user |
| `POST` | `/api/auth/login` | Login and receive a Sanctum token |
| `GET`  | `/api/users/profile/{username}`| Fetch public profile data |

### 🔒 Protected Routes 
*(Requires `Authorization: Bearer <token>` header)*

**Authentication & Profile**
- `GET /api/auth/me` — Retrieve current user profile
- `PUT /api/auth/update` — Update user profile details
- `PUT /api/auth/change-password` — Update user password

**Social Actions**
- `POST /api/users/{username}/follow` — Toggle follow/unfollow a user
- `GET /api/users/suggestions` — Retrieve suggested users to follow

**Feed & Posts**
- `GET /api/posts` — List paginated feed posts
- `POST /api/posts` — Create a new post
- `POST /api/posts/{id}/like` — Toggle post like
- `POST /api/posts/{id}/bookmark` — Toggle post bookmark
- `POST /api/posts/{id}/repost` — Toggle repost status
- `GET /api/bookmarks` — List user's bookmarked posts

**Comments**
- `POST /api/comments` — Add a comment or reply to an existing thread

**Notifications**
- `GET /api/notifications` — List user notifications
- `POST /api/notifications` — Trigger a new notification
- `PUT /api/notifications/mark-read` — Mark a specific (or all) notifications as read
- `DELETE /api/notifications/{id}` — Delete a single notification
- `DELETE /api/notifications` — Clear all notifications

**Discover**
- `GET /api/trends` — Fetch trending hashtags alongside categories

### Example Request

**1. Authenticate**
```bash
curl -X POST http://127.0.0.1:8000/api/auth/login   -H "Content-Type: application/json"   -d '{"email":"user@example.com","password":"secret"}'
```

**2. Fetch Data**
```bash
curl http://127.0.0.1:8000/api/posts   -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

## 🗄 Database Architecture

The backend is designed as an API-first engine. Core models and tables include:

- `users` / `follows`
- `posts` / `likes` / `bookmarks`
- `comments` 
- `notifications`

**Architectural Highlights:**
- **Reposts:** Handled natively via `original_post_id` and `is_retransmission` flags.
- **Nested Replies:** Comments support infinite threading via `parent_id`.
- **Event-Driven:** Notification events automatically fire for likes, comments, replies, follows, and reposts.

---

## 💻 Development Commands

Here are the custom composer commands available to speed up your workflow:

| Command | Action |
|---|---|
| `composer run setup` | Installs dependencies, generates key, migrates DB, and builds assets. |
| `composer run dev` | Boots up the full local development environment. |
| `composer run test` | Executes the PHPUnit test suite. |
| `npm run build` | Compiles frontend assets for production. |

---

## 📄 License

This project is open-sourced software licensed under the [MIT License](LICENSE).