# Content management application

A small CMS: a Laravel API with users, roles and privileges, dynamic pages grouped under a
sortable and nestable menu, and a React front end that reads it all. Every write endpoint is
guarded by a privilege that lives in the database, documented with OpenAPI and covered by tests.

- **Back end** — PHP 8.2+, Laravel 12, MySQL, Laravel Sanctum, l5-swagger
- **Front end** — React 19, Vite, React Router, Tailwind CSS 4, CKEditor 5, dnd-kit
- **Tests** — Pest, 75 feature and unit tests

```
backend/     Laravel API
frontend/    React app (public site and back office)
docker/      nginx and PHP-FPM images
```

## Running it

Two paths. Docker needs nothing installed but Docker; the local path is faster if you already
have PHP and Node.

### With Docker

```bash
git clone <repository-url> laravel-cms
cd laravel-cms
docker compose up -d --build
```

The first boot installs dependencies, runs the migrations, seeds the demo content and generates
the Swagger document. Watch it finish with `docker compose logs -f app`, then open:

| What | Where |
| --- | --- |
| Public site | <http://localhost:5173> |
| Back office | <http://localhost:5173/admin> |
| API | <http://localhost:8000/api> |
| Swagger UI | <http://localhost:8000/api/documentation> |

The user interface is entirely the React app on port 5173. The Laravel side serves
JSON and the Swagger page only, so <http://localhost:8000> redirects to the documentation
rather than rendering anything of its own.

Seeding runs once. To start over: `docker compose down -v && rm -f backend/storage/.seeded`.

### Without Docker

Needs PHP 8.2+ (with `pdo_mysql`, `mbstring`, `gd`, `intl`, `zip`), Composer, Node 20+ and a
MySQL or PostgreSQL server.

```bash
# API
cd backend
composer install
cp .env.example .env
php artisan key:generate
mysql -uroot -e "CREATE DATABASE cms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
# edit .env if your credentials are not root with no password
php artisan migrate --seed
php artisan storage:link          # so uploaded cover images are served
php artisan l5-swagger:generate
php artisan serve                 # http://localhost:8000
```

```bash
# Front end, in a second terminal
cd frontend
npm install
cp .env.example .env              # points at http://localhost:8000
npm run dev                       # http://localhost:5173
```

`php artisan storage:link` matters: without it cover images 404 even though the upload
succeeded.

## Seeded logins

Both accounts use the password `password`.

| Email | Role | May do |
| --- | --- | --- |
| `admin@cms.test` | Administrator | Everything: all 22 privileges |
| `moderator@cms.test` | Moderator | List, add and edit pages; read the menu. Nothing else |

Signing in as the moderator is the quickest way to see the rules working: the Trash, Users,
Roles and Privileges sections disappear from the navigation, and the Delete button disappears
from the pages list. The API refuses those calls with a 403 regardless of what the interface
offers.

## Swagger

The OpenAPI document is generated from PHP 8 attributes on the controllers and API resources, so
it is written next to the code that produces each response rather than in a separate file.

- UI: <http://localhost:8000/api/documentation>
- JSON: <http://localhost:8000/docs>
- Regenerate: `php artisan l5-swagger:generate`

`L5_SWAGGER_GENERATE_ALWAYS=true` in `.env.example` regenerates on every request in development,
which keeps the docs honest while the endpoints move. All 33 operations are documented.

To try a guarded endpoint from the UI: call `POST /api/auth/login`, copy the `token` out of the
response, click **Authorize**, and paste it in.

## Tests

```bash
cd backend
php artisan test
```

Tests run against SQLite in memory, so no database setup is needed and nothing touches your
development data.

```
Tests: 75 passed (242 assertions)
```

What they cover:

| File | Subject |
| --- | --- |
| `AuthTest` | Token issue, bad credentials, 401 without a token, logout revoking a token, login throttling |
| `RolePrivilegeTest` | What a moderator may and may not do, including that a moderator cannot delete a page |
| `PageManagementTest` | Pagination, search, filters, cover uploads, the audit trail, trash and restore |
| `MenuTest` | Nested tree, reordering, circular-order rejection, deleting a branch that still holds pages |
| `PublicContentTest` | Drafts and future dated pages hidden, a scheduled page appearing once its date passes |
| `UserManagementTest` | User CRUD, password hashing, and refusing to delete your own account |
| `RoleManagementTest` | Role and privilege CRUD, and re-granting privileges changing what a role may do |
| `HelperTest`, `SlugHelperTest` | The custom helpers |

## How permissions work

Privileges are rows, not role names in code. A privilege such as `pages.delete` is stored in the
`privileges` table, granted to roles through `privilege_role`, and roles are attached to users
through `role_user`.

Every privilege name doubles as a gate ability. One `Gate::before` hook in
`app/Providers/AppServiceProvider.php` resolves any ability against the user's privileges:

```php
Gate::before(function (User $user, string $ability) {
    return $user->hasPrivilege($ability) ?: null;
});
```

Routes then guard themselves with Laravel's own `can:` middleware, so `routes/api.php` reads as
a list of which privilege each endpoint needs:

```php
Route::get('/', [PageController::class, 'index'])->middleware('can:pages.view');
Route::delete('/', [PageController::class, 'destroy'])->middleware('can:pages.delete');
```

Nothing anywhere checks for the string `admin` or `moderator`. Changing what a role may do is a
row in a pivot table, which is why `RoleManagementTest` can grant `pages.delete` to a role
mid-test and watch the same user go from 403 to 200.

Privilege names read as `group.action` and that shape is enforced when one is created, so a new
privilege is immediately checkable: add `reports.export`, grant it to a role, and
`can:reports.export` works with no deploy.

Form requests only validate. Authorization lives in the route definitions so there is one place
to read it.

## Scheduled publishing

A page is visible to readers when it is published **and** its publish date has passed. That rule
lives in one scope, `Page::scopeVisible()`, which every public endpoint applies:

```php
$query->where('status', PageStatus::Published)
    ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
```

Leaving the publish date empty means "live as soon as it is published". A date in the future
means the page sits in the back end marked **Scheduled** and 404s on the public site until the
date passes.

The seeded content includes both cases so the behaviour is visible immediately:

- **Internship Programme** — a draft under Careers, listed in the back office, absent from the site
- **Autumn Product Launch** — published but dated a week out, marked Scheduled, absent from the site

## Menu

The menu is a tree of any depth ordered by `position`. `GET /api/menus` builds it from a single
query and hydrates the `children` relation in PHP, so nesting costs one query rather than one
per level.

Reordering posts the whole tree to `POST /api/menus/reorder`, with every node's new parent and
position. The payload is rejected if it describes a loop, and a menu item cannot be moved inside
one of its own children. Switching a parent off hides its entire branch from the public site.

Dragging a heading in the back office carries its children with it, and the arrows indent or
outdent. Nothing changes for readers until **Save order** is pressed.

## Audit and trash

Pages record `created_by` and `updated_by`, set from the authenticated user in the model's
`booted()` hook, and both are shown in the back office. Deleting is a soft delete: the row and
its cover image are kept, the page leaves the public site, and an administrator can restore it
from Trash exactly as it was. Only `pages.restore`, which the moderator does not hold, reaches
the trash. Deleting for good also removes the cover file from disk.

## Notes on a few decisions

- **Token auth, not cookies.** Sanctum personal access tokens keep the API stateless and mean
  the same endpoints would serve a mobile client without change.
- **Slugs are stable.** A slug is generated from the title on create and then left alone unless a
  new one is sent explicitly, so renaming a page does not break its public URL. The generator
  counts soft deleted rows, so a slug is never quietly reused.
- **Cover uploads use POST with `_method=PUT`.** PHP only populates uploaded files on POST
  requests, so an update sends multipart as POST with a spoofed method. Both are accepted.
- **An empty file input cannot blank a cover.** Clearing an image is an explicit `remove_cover`
  flag rather than a side effect of submitting the form without a file.
- **Deleting a menu item is refused while pages are filed under it**, including pages in the
  trash, rather than letting the foreign key fail.
- **CORS is limited to `FRONTEND_URL`** rather than left open.
- **Login is rate limited** to six attempts a minute.
- **Page bodies are rendered as HTML** on the public site. The markup comes from CKEditor and an
  authenticated editor, which is the trust boundary a CMS accepts by design.

## Not included

The optional extras in the brief — a mobile client, Arabic and RTL content, and driving scheduled
publishing from a scheduled Artisan command instead of the query-time check — are not built. The
publish rule is enforced at query time by the scope above, which needs no cron to be correct.
