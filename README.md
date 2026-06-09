# BriBooks — Mini Book Writing Platform

A REST API backend for a book creation and publishing platform. Authors write books, reviewers moderate them, and admins publish them.

---

## Tech Stack

- PHP 8.4 / Laravel 13
- MySQL 8 (SQLite in-memory for tests)
- JWT Authentication via `tymon/jwt-auth`
- PHPUnit 12 (33 tests, all passing)
- Document parsing via `phpoffice/phpword`

---

## Installation

### 1. Clone and install dependencies

```bash
git clone <repo-url> bribooks
cd bribooks
composer install
```

### 2. Configure environment

```bash
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
```

Edit `.env` and set your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bribooks
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 3. Run migrations

```bash
php artisan migrate
```

### 4. Start the development server

```bash
php artisan serve
```

The API will be available at `http://127.0.0.1:8000/api`.

---

## Running Tests

Tests use SQLite in-memory — no database setup needed.

```bash
php artisan test
```

Expected output: **33 tests, 75 assertions, all green.**

---

## API Reference

### Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/register` | Register a new user |
| POST | `/api/login` | Login, receive JWT |
| GET | `/api/profile` | Get current user profile |
| POST | `/api/logout` | Invalidate JWT |
| POST | `/api/refresh` | Refresh JWT |

**Register body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "secret123",
  "password_confirmation": "secret123",
  "role": "author"
}
```

`role` can be `author`, `reviewer`, or `admin`. Defaults to `author`.

All authenticated endpoints require: `Authorization: Bearer <token>`

---

### Books

| Method | Endpoint | Role | Description |
|--------|----------|------|-------------|
| GET | `/api/books` | Author | List own books (paginated) |
| POST | `/api/books` | Author | Create a book |
| GET | `/api/books/{id}` | Author | Get a book with chapters/pages |
| PUT | `/api/books/{id}` | Author | Update (auto-snapshots before saving) |
| DELETE | `/api/books/{id}` | Author | Soft-delete a book |

---

### Book Versions

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/books/{id}/versions` | List all version snapshots |
| POST | `/api/books/{id}/versions` | Manually create a snapshot |
| GET | `/api/books/{id}/versions/{versionId}` | Get full snapshot |
| POST | `/api/books/{id}/versions/{versionId}/rollback` | Restore to a past version |

---

### Chapters

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/books/{id}/chapters` | List chapters |
| POST | `/api/books/{id}/chapters` | Add a chapter |
| PUT | `/api/chapters/{id}` | Update a chapter |
| DELETE | `/api/chapters/{id}` | Delete a chapter |

---

### Pages

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/chapters/{id}/pages` | List pages |
| POST | `/api/chapters/{id}/pages` | Add a page |
| PUT | `/api/pages/{id}` | Update a page |
| DELETE | `/api/pages/{id}` | Delete a page |

---

### Document Upload

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/books/{id}/upload` | Upload `.doc` or `.docx`, auto-converts to HTML pages |

**Body:** `multipart/form-data` with field `file`.

The document is split into ~500-word HTML chunks, each becoming a page inside a new chapter.

---

### Review Workflow

| Method | Endpoint | Role | Description |
|--------|----------|------|-------------|
| POST | `/api/books/{id}/submit` | Author | Submit for review (runs moderation first) |
| POST | `/api/books/{id}/approve` | Reviewer | Approve with optional `notes` |
| POST | `/api/books/{id}/reject` | Reviewer | Reject with required `reason` |
| POST | `/api/books/{id}/publish` | Admin | Publish an approved book |

**Status flow:**

```
draft → submitted → under_review → approved → published
                                 ↘ rejected → (re-submit)
```

---

### Dashboard

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/dashboard` | Role-aware dashboard stats |

Authors see their book counts per status. Reviewers see pending books. Admins see platform-wide stats.

---

## Architecture Decisions

### Version Snapshots

Every time a book is updated via `PUT /api/books/{id}`, a snapshot is taken automatically before the update. Snapshots store the full book state (metadata + chapters + pages) as JSON in `book_versions.snapshot`. This makes rollback straightforward — restore the JSON, recreate chapters and pages.

Trade-off: JSON snapshots grow large for books with many pages. For very large books, a diff-based approach would save storage. The JSON approach was chosen because it is simple, easy to query, and makes rollback trivial.

### Moderation

Moderation runs synchronously on submit. In production this would be a queued job, especially if calling an external API. The current keyword-matching implementation is fast enough to run inline. The word lists are injected via the `ModerationService` class so they are easy to extend or replace.

### Role-Based Access

Roles are stored as an enum on `users` and embedded in the JWT payload as a custom claim (`role`). This avoids a database query on every request. The trade-off is that a role change does not take effect until the old token expires — acceptable for this project.

### Published Books Are Read-Only

Write operations on published books are rejected with HTTP 422. Enforced at the controller level, not the database level, because the constraint is about workflow state.

---

## Assumptions

- Role is accepted at registration to make the system testable. In a real product, role assignment would be an admin operation.
- Rejected books can be re-submitted directly without reverting to `draft`. The workflow goes `rejected → submitted`.
- Document upload creates one chapter per uploaded file. Multiple uploads append more chapters.
- The `order` field is managed by the client. If omitted, new chapters/pages are appended to the end automatically.
