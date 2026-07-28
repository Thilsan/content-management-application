# Content Management Application

Laravel Developer take-home assignment. A CMS backend (users, roles, privileges, pages, a
sortable/nestable menu) with a React frontend and a Flutter mobile client, all talking to the
same API.

Stack:
- **Backend:** PHP 8.2+, Laravel 12, MySQL, Sanctum, l5-swagger
- **Frontend:** React 19, Vite, Tailwind 4, CKEditor 5
- **Mobile:** Flutter (read-only client)
- **Tests:** Pest, 95 tests on the backend, 16 on the mobile app

Folder layout:

```
backend/     Laravel API
frontend/    React app (public site + admin)
mobile/      Flutter app (bonus)
docker/      nginx + php-fpm config
```

## Setup

You can run this with Docker or without it. I used Docker for the final check but did most of
the actual development running things locally, so both should work.

### Docker

```bash
git clone <repo-url> laravel-cms
cd laravel-cms
docker compose up -d --build
```

First run takes a bit, it installs composer deps, migrates, seeds and builds the swagger docs.
Tail the logs if you want to watch it happen (`docker compose logs -f app`). Once it's up:

- Site: http://localhost:5173
- Admin: http://localhost:5173/admin
- API: http://localhost:8000/api
- Swagger: http://localhost:8000/api/documentation

Note that http://localhost:8000 by itself just redirects to swagger. There's no Blade view or
anything there, the whole UI is the React app.

If you want to reseed from scratch: `docker compose down -v && rm -f backend/storage/.seeded`

### Without Docker

You need PHP 8.2+ with the usual extensions (pdo_mysql, mbstring, gd, intl, zip), Composer,
Node 20+, and a MySQL/Postgres server running somewhere.

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
mysql -uroot -e "CREATE DATABASE cms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
# change DB_* in .env if root/no-password isn't your setup
php artisan migrate --seed
php artisan storage:link
php artisan l5-swagger:generate
php artisan serve
```

Then in another terminal:

```bash
cd frontend
npm install
cp .env.example .env
npm run dev
```

Don't skip `php artisan storage:link`. Cover images will 404 without it even though the upload
itself works fine (learned that one the hard way while testing).

## Logins

Both seeded accounts use `password` as the password.

- `admin@cms.test`, has every privilege
- `moderator@cms.test`, can list/add/edit pages and view the menu, nothing else

Easiest way to see the privilege system actually doing something is to log in as the moderator.
Trash, Users, Roles and Privileges disappear from the nav, and there's no delete button on
pages. And it's not just hidden in the UI, the API returns 403 on those routes no matter what
the frontend shows.

## Swagger

http://localhost:8000/api/documentation has all 33 endpoints, documented straight from PHP
attributes on the controllers and resources instead of a separate yaml file, so it's less likely
to go stale. Regenerate with `php artisan l5-swagger:generate`, or leave
`L5_SWAGGER_GENERATE_ALWAYS=true` in your .env and it rebuilds on every request while you're
developing.

To test an authenticated route from the Swagger UI: hit `POST /api/auth/login`, copy the token
from the response, click Authorize, paste it in.

## Tests

```bash
cd backend
php artisan test
```

Runs against an in-memory SQLite db so it won't touch anything on your machine. Currently:

```
Tests:    95 passed (308 assertions)
```

Quick rundown of what's covered:

- `AuthTest`: login/logout, bad credentials, throttling, 401s
- `RolePrivilegeTest`: what a moderator can and can't do (the important one re: the brief)
- `PageManagementTest`: pagination, search, filters, cover uploads, audit fields, trash/restore
- `MenuTest`: nesting, reorder, rejecting a circular reference
- `PublicContentTest` and `ScheduledPublishingTest`: drafts and future-dated pages staying hidden
- `LocalisationTest`: the Arabic fallback logic
- `UserManagementTest` and `RoleManagementTest`: CRUD, and permission changes taking effect right away
- `HelperTest`, `SlugHelperTest`: the custom helper functions

## How the permission system works

This was probably the part of the brief I paid the most attention to, the line about privileges
being data and not hard-coded role names. So there's a `privileges` table, roles pull from it
through `privilege_role`, and users get roles through `role_user`. A privilege name like
`pages.delete` is the gate ability itself. There's a single `Gate::before` in
`AppServiceProvider` that checks it:

```php
Gate::before(function (User $user, string $ability) {
    return $user->hasPrivilege($ability) ?: null;
});
```

Routes just declare what they need:

```php
Route::delete('/', [PageController::class, 'destroy'])->middleware('can:pages.delete');
```

No `if ($user->role === 'admin')` anywhere. You can grant or revoke a privilege on a role and it
takes effect right away, no deploy needed. There's a test that grants `pages.delete` to a role
mid-test and checks the same user goes from 403 to 200 on the next call.

## Scheduled publishing

Pages have a `published_at` date, and a page only shows on the public site once that date has
passed. Instead of checking the date on every read, there's an `is_live` boolean on the row and
an artisan command that flips it:

```bash
php artisan pages:publish-due
```

It promotes anything due, and also un-publishes anything that got pulled back to draft or
rescheduled later (wanted the reverse case handled too, not just the happy path). Registered in
`routes/console.php` to run every minute:

```bash
php artisan schedule:work   # locally
```

```
* * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1   # on a real server
```

The Docker `scheduler` container just runs `schedule:work` for you, so nothing to configure there.

One wrinkle: saving a page with no publish date goes live immediately, no reason to make it wait
for the next minute-tick, but a page with a future date does wait on the scheduler. Both cases
are in the seed data so you can see them. "Internship Programme" is a plain draft, and "Autumn
Product Launch" is published but dated a week out, so it shows as Scheduled in the admin and
404s on the public site until the command runs and the date has passed.

## Menu

Nested to any depth, ordered by `position`. `GET /api/menus` fetches everything in one query and
builds the tree in PHP rather than N+1'ing per level.

Reordering sends the whole tree at once (`POST /api/menus/reorder`) with each node's new parent
+ position. Rejects anything that would create a loop, and you can't nest an item under its own
child. Turning off a parent hides the whole branch from the public site, children included.

In the admin, dragging a row takes its children with it, and there are indent/outdent buttons.
Nothing actually changes until you hit Save.

## Audit trail + trash

Pages track `created_by` and `updated_by`, set automatically from the logged-in user (see the
model's `booted()`). Delete is soft: the row and cover image stick around, the page comes off
the public site, and an admin can restore it later from Trash. Moderators don't have
`pages.restore` so they can't reach the trash at all, force-delete included. Force-deleting also
cleans up the cover file from disk.

## A few implementation notes

Things that might not be obvious from just reading the code:

- Token auth (Sanctum), not sessions. Made it trivial to reuse the same API for the mobile app.
- Slugs don't change on rename. Generated once from the title and kept afterward unless you
  explicitly set a new one, otherwise editing a title would break the page's public URL. The
  slug generator also checks soft-deleted rows so a slug never gets silently reused.
- Uploading a new cover image on an update goes through as `POST` with a `_method=PUT` field,
  because PHP doesn't populate `$_FILES` on a real PUT request. Both are accepted server-side.
- Clearing a cover image is an explicit `remove_cover` flag. An update request with no file just
  leaves the existing cover alone, it doesn't accidentally wipe it.
- You can't delete a menu item that still has pages under it, including trashed ones.
- `composer.json` pins `config.platform.php` to 8.2.0. Without this, composer resolves against
  whatever PHP you're running locally, and if that happens to be 8.4 it'll install Symfony 8,
  which actually needs PHP 8.4.1+. So the lockfile would end up requiring a newer PHP than the
  project claims to support. I only caught this because the Docker container (PHP 8.3) refused
  to boot.
- CORS is locked to `FRONTEND_URL`, not wide open.
- Login is rate-limited, 6 attempts a minute.
- Page bodies render as raw HTML on the site. It's CKEditor output from an authenticated editor,
  so that's an accepted trust boundary for a CMS, not an oversight.

## Arabic / RTL (bonus)

Pages and menu items can have an Arabic title/body alongside the English ones (just extra
columns, didn't bother with a separate translations table since there are only two languages).

Public API takes a `?lang=ar` param:

```bash
curl 'http://localhost:8000/api/public/pages/who-we-are?lang=ar'
```

and gives you back one already-resolved language, so the frontend isn't picking between fields:

```json
{ "title": "من نحن", "locale": "ar", "direction": "rtl", "is_translated": true }
```

Fallback logic: if a page has no Arabic at all, it serves in English and says
`is_translated: false`. If it's only partially translated (say just the title), it still falls
back to English entirely. Showing an Arabic heading over an English body seemed worse than just
picking one language and being consistent about it. Four of the eight seeded pages have Arabic
content and the other four don't, so you can see both cases on the site.

The language switch in the header (EN / عربي) sets `dir` and `lang` on the page and remembers
your choice. Layout flips because I used logical Tailwind properties (`ms-`, `ps-`, `start-`,
`border-s`, etc.) instead of left/right everywhere, so there's no separate mirrored stylesheet to
maintain, just a small `[dir='rtl']` block in index.css for the handful of things that don't flip
on their own (the typography plugin's blockquote border, the select arrow, that kind of thing).

A couple of details worth mentioning. The article body has its own `dir` attribute, so if a page
falls back to English inside an otherwise-Arabic session, that one page still reads left to
right. And in the admin, the page editor has its own EN/Arabic toggle that switches CKEditor
itself into RTL mode. You have to tell CKEditor the content language directly, styling the
wrapper div doesn't move the cursor around correctly.

## Mobile app (bonus)

Read-only Flutter client in `mobile/`. Logs in against the same Sanctum endpoints, lists pages
under the menu, opens one.

```bash
cd mobile
flutter pub get
flutter run
```

Defaults to `http://localhost:8000`, which works for the iOS simulator. Android emulator needs:

```bash
flutter run --dart-define=API_URL=http://10.0.2.2:8000
```

```bash
flutter test      # 16 tests
flutter analyze
```

It hits the authenticated endpoints, same as the admin panel, not the public ones, so signing in
actually changes what you can see. A moderator account gets the same 403s here as everywhere
else, and drafts and scheduled pages show up with a badge just like in the web admin. The token
gets stored on the device and re-checked against `/api/auth/me` on launch, so if it's been
revoked you land back on the login screen instead of a blank list.

It also has the EN/Arabic toggle, with the same fallback rule as the website. That rule is
reimplemented in Dart, since the authenticated endpoints return both raw fields rather than one
resolved value. It's a bit of duplication, but it's covered by tests on both sides so they
shouldn't drift apart.

Page bodies render through `flutter_html`. One iOS-specific thing worth mentioning:
`ios/Runner/Info.plist` has an `NSAllowsLocalNetworking` exception in it, otherwise iOS blocks
the plain http request to a local API. It's scoped narrowly to local network traffic, not
general cleartext HTTP.

## Bonus items

All three from the brief are done:

- Artisan command driving scheduled publishing (`pages:publish-due`, covered above)
- Mobile client (`mobile/`, Flutter)
- Arabic / RTL, working on both the website and the mobile app
