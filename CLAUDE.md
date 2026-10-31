# QRode System Guidelines

QRode is a web and mobile-based school visitor and gate pass management system with QR code verification (University of Cebu – Main Campus).

## Stack

- Laravel 10.x (installed: 10.50.3) / PHP 8.2+, MySQL 8.0, Bootstrap 5 (CDN), Vanilla JS
- QR generation: `simplesoftwareio/simple-qrcode` (SVG output only — Imagick is NOT installed on XAMPP)
- PDF reports: `barryvdh/laravel-dompdf`
- QR scanning in the browser: `html5-qrcode` (CDN)

> The manuscript mentions Laravel 11, but the project is installed on Laravel 10. Do not write Laravel 11-only code
> (e.g. register middleware aliases in `app/Http/Kernel.php` `$middlewareAliases`, not `bootstrap/app.php`).

## Core Entities

`visitors`, `blacklist`, `gate_passes`, `gates`, `traffic_history`, `facial_analysis`, `facial_analysis_matches`
(also: `users` = Admin/Guard staff, `walk_ins`, `live_gate_traffic`, `system_logs`)

## Roles & RBAC

Roles: `Visitor`, `Guard`, `Admin`.

- Visitors live in the `visitors` table; Admin/Guard live in `users` (`role` = `admin` | `guard`).
- Auth is **custom session-based**, not Laravel Auth/Guards: `visitor_id` for visitors; `user_id`, `user_role`, `user_name` for staff.
- Route protection is by middleware: `visitor.auth` (EnsureVisitorAuthenticated) and `staff:<roles>` (EnsureStaffRole).
- The Guard's assigned gate is `session('gate_id')`. Guard queries MUST filter by it. Never trust a gate id sent by the browser.

## Guard Permissions (Strict Scope)

Guards CAN:
- Perform Guard-Assisted Walk-in Registration
- Scan QR pass, cross-check against `blacklist`, and view scan alert status
- View the daily visitor log for their specific assigned gate (`Gate 2`, `Gate 3`, or `Gate 4`)
- Edit visitor information from the Guard Terminal
- View system log history filtered to their gate session
- Generate and filter PDF reports restricted to their assigned gate traffic

Guards CANNOT:
- Run online pre-registration
- Delete accounts
- Perform system-wide admin actions

## Facial Duplicate Detection

- Real face recognition: Python FastAPI service in `ai_service/` (InsightFace `buffalo_l`, ArcFace, 512-dim), run on
  `http://127.0.0.1:5000`. Start it with `uvicorn ai_service:app --host 127.0.0.1 --port 5000` next to `php artisan serve`.
- `app/Services/FacialVerificationService.php`: `extract512DimVector($relativePath)` POSTs the stored image to
  `/extract-vector` (`Http::attach`) and returns the vector or `null`; `calculateCosineSimilarity()`;
  `processSelfieAndCheckDuplicate()`.
- Match threshold: **0.70** cosine similarity (`QRODE_FACE_THRESHOLD`). A new selfie is compared against all stored visitor
  vectors AND active blacklist reference photos (blacklist vectors are cached in `blacklist.embedding`). Any match is recorded
  in `facial_analysis_matches` and the visitor is set to `flagged` for Admin review.
- Photo with no face / several faces / not an image: registration is rejected and the visitor retakes the selfie.
  AI service down: the visitor is `flagged` for manual Admin review (never silently skip the check).
- After switching from the old hash simulation, run `php artisan qrode:reembed-faces` once to recompute stored vectors.

## UI Design Tokens

All colors live as CSS variables at the top of `public/css/style.css`, sampled from the manuscript's UI mockups (navy
`#10131f`, page `#f4f5fa`, accent `#3d5afe`, success `#16a34a`, danger `#dc2626`). `flagged` is amber (not in the mockups).
Use the palette classes (`badge-pending|flagged|approved|valid|denied|blacklisted`, `btn-approve|deny|blacklist`,
`alert-flagged`, `capture-box`), never ad-hoc Bootstrap color utilities like `text-bg-*`.

## Conventions

- Controllers look records up with `Model::find($id)` / `where()`; no route-model binding.
- Uploaded files use the `public` disk (`php artisan storage:link`); in views use `asset('storage/' . $path)`.
- Every state-changing action should call `Qrode::log($userId, $action, $details)` (writes `system_logs`).
- Every POST form needs `@csrf`; AJAX sends the `X-CSRF-TOKEN` header.
- Set `'timezone' => 'Asia/Manila'` in `config/app.php` so "today" / daily logs roll over at local midnight.
