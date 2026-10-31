# QRode — Laravel / PHP / MySQL Implementation

This is a real Laravel implementation of QRode, matching your manuscript's
Chapter III architecture: migrations, Eloquent models, Blade views, and
controllers, running on MySQL.

**Important:** this was built in a sandbox with no access to Composer or
Packagist, so none of this could actually be installed or run before
delivery. It's a careful, direct translation of a fully working Node.js
prototype (same schema, same routes, same business logic — including the
QR scan cooldown, facial-duplicate flagging, and blacklist enforcement),
and the two riskiest pieces (the facial-similarity algorithm and the QR
token generator) were unit-tested standalone in plain PHP before packaging.
But the full Laravel framework wiring — routing, middleware registration,
Eloquent behavior, Blade rendering — has **not** been run end-to-end. Budget
time to debug.

## Setup

### 1. Create a fresh Laravel project

```bash
composer create-project laravel/laravel qrode-laravel "^10.0"
cd qrode-laravel
```

### 2. Copy these files into it

Copy every folder from this package into the matching folder in your fresh
Laravel project, **overwriting** where files already exist:

```
app/Models/          → app/Models/           (User.php overwrites the default one)
app/Services/         → app/Services/          (new folder)
app/Support/           → app/Support/            (new folder)
app/Http/Middleware/    → app/Http/Middleware/     (add to existing files, don't delete others)
app/Http/Controllers/    → app/Http/Controllers/    (add to existing files)
database/migrations/      → database/migrations/     (add to existing — keep Laravel's default users/cache/jobs migrations too)
database/seeders/          → database/seeders/         (overwrites DatabaseSeeder.php)
routes/web.php               → routes/web.php            (overwrites the default one)
resources/views/               → resources/views/           (add — delete the default welcome.blade.php if you want)
public/css/style.css             → public/css/style.css
public/js/camera-capture.js        → public/js/camera-capture.js
```

### 3. Install the two extra packages

```bash
composer require simplesoftwareio/simple-qrcode
composer require barryvdh/laravel-dompdf
```

### 4. Register the middleware

Laravel 10 — open `app/Http/Kernel.php` and add these two lines inside the
`$routeMiddleware` array:

```php
'visitor.auth' => \App\Http\Middleware\EnsureVisitorAuthenticated::class,
'staff' => \App\Http\Middleware\EnsureStaffRole::class,
```

Laravel 11 — instead, open `bootstrap/app.php` and add inside
`->withMiddleware(function (Middleware $middleware) { ... })`:

```php
$middleware->alias([
    'visitor.auth' => \App\Http\Middleware\EnsureVisitorAuthenticated::class,
    'staff' => \App\Http\Middleware\EnsureStaffRole::class,
]);
```

### 5. Configure the database

In `.env`, point at your XAMPP MySQL instance (create a `qrode` database in
phpMyAdmin first):

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=qrode
DB_USERNAME=root
DB_PASSWORD=
```

Also add this line (used by the QR pass token generator):

```
QRODE_SECRET=change-this-to-something-random
```

### 6. Migrate, seed, and link storage

```bash
php artisan migrate
php artisan db:seed
php artisan storage:link
```

`storage:link` is required — uploaded selfies/IDs are stored in
`storage/app/public/...` and served at `/storage/...` through that symlink.
Without it, every uploaded photo will 404.

### 7. Run it

```bash
php artisan serve
```

Open `http://localhost:8000`. Default accounts (seeded in step 6):
- **Admin:** admin@qrode.uc.edu.ph / admin123
- **Guard:** guard@qrode.uc.edu.ph / guard123

## What to check first if something breaks

- **Blade `@include` paths use dots, not slashes** (`partials.head`, not
  `partials/head`) — I've been careful about this throughout, but if a view
  fails to render, check this first.
- **CSRF tokens** — every POST form includes `@csrf`. The AJAX scan-verify
  call in `guard/scan.blade.php` sends the token via the `X-CSRF-TOKEN`
  header instead, reading it from `csrf_token()`. If that call gets a 419
  error, this is why.
- **Route model binding vs plain IDs** — controllers take plain `int $id`
  parameters and look records up manually (`Visitor::find($id)`), matching
  the Node version's style, rather than using Laravel's automatic route
  model binding. This was a deliberate choice to keep the translation
  closer to already-tested logic, not an oversight.
- **`storage_path` vs `public_path`** — uploaded files are referenced in
  views as `asset('storage/' . $path)`. If images don't load, re-check step
  6's `storage:link`.
- **The `matches` table** — Eloquent reserves `Match` awkwardly against
  PHP's `match` expression, so the model is named `FaceMatch` but its
  `$table` property points at the real `matches` table. Don't rename the
  table to match the class name.

## Facial duplicate detection — same disclosed limitation as before

`app/Services/FaceSimilarityService.php` is a direct PHP port of the
Node prototype's simulated embedding approach — **not a real
face-recognition model**. It deterministically derives a 512-dimension
vector from the raw bytes of the uploaded image and compares vectors with
real cosine similarity. This was unit-tested standalone (see the
conversation this was built in) and confirmed to: produce identical
vectors for identical image bytes, near-zero similarity for unrelated
images, and zero false positives across 200 random pairs.

It will correctly flag a genuinely identical/near-identical re-uploaded
image. It will **not** recognize two different photos of the same person's
face, because it reads file bytes, not facial features. Swap
`extractEmbedding()` for a real model call to go to production.

## Laravel/PHP piece ↔ Node prototype mapping

If you're keeping the Node prototype around for comparison or reference:

| Laravel | Node.js equivalent |
|---|---|
| `app/Http/Controllers/*.php` | `routes/*.js` |
| `resources/views/**/*.blade.php` | `views/**/*.ejs` |
| `database/migrations/*.php` | `db/schema.sql` |
| Eloquent models | raw `better-sqlite3` queries |
| `EnsureStaffRole` / `EnsureVisitorAuthenticated` middleware | `lib/auth.js` |
| `FaceSimilarityService` | `lib/face.js` |
| `Qrode::generatePassToken()` / `Qrode::log()` | `lib/util.js` |
| `simplesoftwareio/simple-qrcode` | `qrcode` npm package |
| `barryvdh/laravel-dompdf` | `pdfkit` |
| `public/js/camera-capture.js`, `html5-qrcode` (CDN) | identical — copied unchanged, both are plain browser JS |
