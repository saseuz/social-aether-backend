# Aether Social Backend

Aether Social Backend is a Laravel-based API server powering a modern social experience with:
- user registration and token-based authentication
- profiles, follows, and user suggestions
- posts with likes, bookmarks, reposts, and threaded comments
- notifications for likes, comments, replies, follows, and reposts
- trending hashtag discovery
- Filament-ready admin support and Vite asset tooling

---

## Tech stack

- PHP ^8.3
- Laravel Framework ^13.8
- Laravel Sanctum for API authentication
- Filament for admin UI and panel support
- Vite + Tailwind CSS for frontend asset processing
- PHPUnit for automated testing

---

## Features

- Auth: register, login, profile fetch, update profile, password change
- Social graph: follow / unfollow other users, follow suggestions
- Feed: fetch all posts with nested repost, comment, like, bookmark state
- Post actions: create posts, like, bookmark, repost
- Comments: create comments and threaded replies
- Notifications: fetch, create, mark read, delete, clear all
- Trends: detect hashtag popularity across posts

---

## Requirements

- PHP 8.3+
- Composer
- Node.js 18+ / npm
- A database supported by Laravel (SQLite, MySQL, PostgreSQL)

---

## Quick setup

```bash
cd /home/zypp/code/aether-social-workspace/social-app-backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

If you want a one-command setup for local development, you can use the composer script:

```bash
composer run setup
```

---

## Environment configuration

Update `.env` for your environment, for example:

```env
APP_NAME="Aether Social"
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database/database.sqlite

SANCTUM_STATEFUL_DOMAINS=127.0.0.1
SESSION_DRIVER=file
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
```

If using SQLite, create the database file before running migrations:

```bash
touch database/database.sqlite
```

---

## Running locally

Start the backend and asset pipeline:

```bash
composer run dev
```

This launches a local Laravel server, Vite dev server, queue listener, and log watcher.

For a simple backend-only start:

```bash
php artisan serve
```

---

## API overview

All routes are prefixed with `/api`.

### Public routes

- `POST /api/auth/register` — register a new user
- `POST /api/auth/login` — login and receive a Sanctum token
- `GET /api/users/profile/{username}` — fetch public profile data

### Protected routes (require `Authorization: Bearer <token>`)

#### Auth
- `GET /api/auth/me` — current user profile
- `PUT /api/auth/update` — update user profile
- `PUT /api/auth/change-password` — update password

#### User social actions
- `POST /api/users/{username}/follow` — follow / unfollow a user
- `GET /api/users/suggestions` — suggested users to follow

#### Feed and posts
- `GET /api/posts` — list feed posts
- `POST /api/posts` — create a new post
- `POST /api/posts/{id}/like` — toggle like
- `POST /api/posts/{id}/bookmark` — toggle bookmark
- `POST /api/posts/{id}/repost` — toggle repost
- `GET /api/bookmarks` — list bookmarked posts

#### Comments
- `POST /api/comments` — add a comment or reply

#### Notifications
- `GET /api/notifications` — list notifications
- `POST /api/notifications` — create a notification
- `PUT /api/notifications/mark-read` — mark one or all notifications as read
- `DELETE /api/notifications` — clear all notifications
- `DELETE /api/notifications/{id}` — delete a single notification

#### Trends
- `GET /api/trends` — trending hashtags with categories

---

## Example request

```bash
curl -X POST http://127.0.0.1:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"secret"}'
```

Use the returned token for protected requests:

```bash
curl http://127.0.0.1:8000/api/posts \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

## Database schema

Core tables include:
- `users`
- `posts`
- `comments`
- `likes`
- `bookmarks`
- `follows`
- `notifications`

The project supports:
- reposts via `original_post_id` and `is_retransmission`
- nested comment replies via `parent_id`
- user-to-user follow relationships
- notification events for likes, comments, replies, follows, and reposts

---

## Development commands

- `composer run setup` — install dependencies, generate key, migrate, build assets
- `composer run dev` — start dev environment
- `composer run test` — execute PHPUnit tests
- `npm run build` — compile frontend assets

---

## Notes

- Laravel Sanctum secures all protected API endpoints.
- Filament is installed and ready for admin panel support.
- The backend is designed as an API-first social app engine.

---

## License

This project is licensed under the MIT License.
