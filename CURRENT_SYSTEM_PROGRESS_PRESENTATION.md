# MotoRepair Pro — Current System Progress Presentation

**A five-member presentation script, demonstration plan, and technical walkthrough**

---

## About This Document

This document is a **presentation script + demonstration guide** for five IT students presenting the current
progress of the project in this repository.

Everything written here was taken directly from the actual source code in this project. Where the code does
not yet do something, this document says so plainly instead of pretending it works. No source file was
changed to produce this document.

**Files this document is based on (all verified):**

| Area | Files inspected |
|---|---|
| Laravel layer | `composer.json`, `.env`, `routes/web.php`, `bootstrap/app.php`, `app/Models/User.php`, `app/Http/Controllers/Controller.php`, `app/Providers/AppServiceProvider.php`, `database/migrations/*`, `database/seeders/DatabaseSeeder.php`, `resources/views/welcome.blade.php`, `tests/*`, `phpunit.xml`, `package.json`, `vite.config.js` |
| Application layer | Every file in `legacy/` (27 PHP pages + `includes/`, `config/`, `phpmailer/`) |
| Front-end assets | `public/assets/css/portal.css`, `public/assets/js/main.js`, `public/assets/pic/Screenshot 2026-07-20 154440.png`, `public/build/*` |
| Database | `legacy/motorcycle_repair_shop.sql` (schema + sample rows) |
| Docs | `legacy/README.md` |

> **Note on the two halves of this project.** The repository contains a fresh **Laravel 12** installation
> *and* the real working application, which is a **plain PHP + MySQL (mysqli)** application stored in the
> `legacy/` folder. Laravel currently acts only as a **URL wrapper/router** around the legacy pages. This is
> explained fully in Section 4, and Member 4 should be the one who presents it.

---

## 1. System Overview

### 1.1 System name

**MotoRepair Pro** — Motorcycle Repair Shop Management System.

(The name comes from the page titles and the `legacy/README.md`. One small inconsistency worth mentioning if
asked: the Laravel `.env` still has `APP_NAME=Laravel`, so the Laravel "branding" was never renamed.)

### 1.2 What the system is

MotoRepair Pro is a **web-based management system for a motorcycle repair shop**. It covers the whole service
cycle in one application, with three separate portals:

1. A **customer portal** where a motorcycle owner books a repair and tracks its progress.
2. A **mechanic portal** where a technician sees assigned jobs and updates their status.
3. An **admin portal** where the shop owner manages jobs, mechanics, customers, spare-part inventory,
   invoices and reports.

**Address of the running system:** `http://localhost:8000` (from `APP_URL` in `.env`, which matches
`php artisan serve`).

### 1.3 The problem it aims to solve

Small motorcycle repair shops typically run on paper job orders, a notebook for the service queue, a
whiteboard for mechanic assignments, and a calculator for parts + labour. That causes four concrete problems:

| Problem in a manual shop | How MotoRepair Pro addresses it |
|---|---|
| Walk-in customers do not know how long the repair will take | Customers get a **Booking ID** and a **Queue Number**, and can track progress online any time |
| The shop cannot answer "where is my motorcycle?" on the phone all day | A public **Track Status** page shows the repair stage, parts used and running total from just the Booking ID |
| Parts run out silently and jobs are delayed | An **inventory module** tracks stock per part and the dashboard raises a **low-stock alert** below 10 units |
| The owner cannot tell which service earns the most, or which mechanic is fastest | A **reports module** aggregates revenue by date, by service type, by mechanic and by part |

### 1.4 Target users

The system has exactly three roles, matched to the `users.role` column
(`enum('customer','admin','mechanic')` in the database):

| Role | Who they are | What they can do in the system |
|---|---|---|
| **Customer** | Motorcycle owners / walk-in clients | Register, log in, book a service, track a repair by Booking ID, see their own booking history and costs, cancel a *pending* booking |
| **Mechanic** | Shop technicians | Log in, see a dashboard of assigned jobs, list/search their repairs, open a repair, update its status, add work notes, see parts used and the running cost |
| **Admin** | Shop owner / manager | Log in, see the business dashboard, manage all jobs, assign mechanics, manage inventory, add/edit/delete mechanics, view/delete customers, view mechanic performance, open repair-cost details, generate invoices, run reports |

**Guests (not logged in)** can still open the customer home page, submit a booking and track a status — the
booking processor deliberately accepts a logged-out visitor by storing `user_id = NULL`
(`legacy/process-booking.php`, line 10).

### 1.5 The five main features

1. **Online service booking** with automatic Booking ID + daily Queue Number.
2. **Live repair tracking** with a progress bar and a 4-step timeline.
3. **Mechanic job management** — assignment, status updates, work notes, parts consumption.
4. **Spare-parts inventory** with automatic stock deduction when a part is used on a repair.
5. **Management reporting** — revenue trend, service-type breakdown, mechanic performance, top customers,
   stock levels and most-used parts.

### 1.6 Technology footprint (what is really in the repository)

| Layer | What the project actually uses | Evidence |
|---|---|---|
| Server language | PHP 8.2 (XAMPP) | `composer.json` requires `php: ^8.2`; SQL dump header says PHP 8.2.12 |
| Application framework | **Laravel 12** — used only as a router/wrapper | `composer.json` (`laravel/framework: ^12.0`), `routes/web.php` |
| Real application code | Plain procedural PHP with **mysqli** (no framework) | all of `legacy/`, e.g. `legacy/config/database.php` |
| Database | **MySQL / MariaDB**, database name `motorcycle_repair_shop` | `legacy/config/database.php`, `legacy/motorcycle_repair_shop.sql` (MariaDB 10.4.32) |
| Front-end framework | **Bootstrap 5.1.3** via CDN | `<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/...">` on every page |
| Icons | **Font Awesome 6.0.0** via CDN | every portal page |
| Charts | **Chart.js** via CDN | `legacy/admin/dashboard.php`, `legacy/admin/reports.php`, `legacy/mechanic/dashboard.php` |
| E-mail | **PHPMailer**, bundled manually inside the repo | `legacy/phpmailer/src/*`, `legacy/send-notification.php` |
| Styling system | One custom dark stylesheet, `public/assets/css/portal.css` | linked by all 18 portal pages |
| Laravel extras that exist but are unused | Tailwind CSS 4 + Vite build, Blade welcome view, Pest tests, Eloquent `User` model | `resources/`, `public/build/`, `tests/`, `app/Models/User.php` |

### 1.7 Architecture at a glance

```
                        BROWSER
                           |
                           v
   +--------------------------------------------------+
   |  LARAVEL 12  (the "wrapper" / front controller)   |
   |  public/index.php  ->  routes/web.php             |
   |                                                   |
   |  runLegacy('login.php')                           |
   |     chdir(dirname($file));  require $file;  exit; |
   +--------------------------------------------------+
                           |
                           v
   +--------------------------------------------------+
   |  LEGACY APPLICATION  (the real system)            |
   |                                                   |
   |  legacy/                                          |
   |    bootstrap.php ....... autoload + config +      |
   |                          session_start()          |
   |    config/database.php . mysqli connection +      |
   |                          "remember me" cookie     |
   |    includes/auth.php ... role guards + helpers    |
   |    login.php, register.php, logout.php,           |
   |    process-booking.php, reset-password.php,       |
   |    send-notification.php  (PHPMailer)             |
   |    admin/ (11 pages)                              |
   |    customer/ (4 pages)                            |
   |    mechanic/ (3 pages)                            |
   +--------------------------------------------------+
                           |
                           v
   +--------------------------------------------------+
   |  MySQL :  motorcycle_repair_shop                  |
   |  users | bookings | repairs | repair_parts | parts|
   +--------------------------------------------------+
```

---

## 2. Current System Features and Development Status

### 2.1 Feature inventory (verified against the source code)

Legend: **Working** = the code path is complete and reachable · **Partial** = it mostly works but something is
missing or incorrect · **Broken** = the code exists but cannot run · **Not started** = no code exists.

| # | Feature | Portal | Page / file | Status |
|---|---|---|---|---|
| 1 | Login with e-mail + password, role-based redirect | all | `legacy/login.php` | **Working** |
| 2 | "Remember me" (7-day cookie, token stored in `users.remember_token`) | all | `legacy/login.php`, `legacy/config/database.php` | **Working** |
| 3 | Customer self-registration with validation + auto-login | customer | `legacy/register.php` | **Working** |
| 4 | Forgot password (1-hour token) + set-new-password page | all | `legacy/login.php`, `legacy/reset-password.php`, `legacy/send-notification.php` | **Working** (depends on SMTP being accepted) |
| 5 | Logout (clears session + both cookies) | all | `legacy/logout.php` | **Working** |
| 6 | Customer home / service catalogue | customer | `legacy/customer/home.php` | **Working** |
| 7 | Book a service | customer | `legacy/customer/book-service.php` → `legacy/process-booking.php` | **Working** |
| 8 | Automatic Booking ID (`BK` + date + random 4 digits) | — | `legacy/includes/auth.php` `generateBookingId()` | **Working** |
| 9 | Automatic daily Queue Number | — | `legacy/includes/auth.php` `getQueueNumber()` | **Working** |
| 10 | Track a repair by Booking ID (progress bar + timeline + parts + total) | customer | `legacy/customer/track-status.php` | **Working** |
| 11 | "My Bookings" list with stats, filters, expandable rows, cost summary | customer | `legacy/customer/booking-status.php` | **Working** |
| 12 | Cancel a pending booking | customer | button in `booking-status.php` → `cancel-booking.php` | **Broken — the target file does not exist** |
| 13 | Mechanic dashboard (3 stat cards + doughnut chart + job list) | mechanic | `legacy/mechanic/dashboard.php` | **Working** |
| 14 | Mechanic "My Repairs" with search + status filter | mechanic | `legacy/mechanic/my-repairs.php` | **Working** |
| 15 | Mechanic updates repair status (with dates + booking sync + cost recalc) | mechanic | `legacy/mechanic/work-on-repair.php` | **Working** |
| 16 | Mechanic adds timestamped work notes | mechanic | `legacy/mechanic/work-on-repair.php` | **Working** |
| 17 | Admin dashboard (4 KPIs + recent bookings + 7-day revenue chart + low-stock alert) | admin | `legacy/admin/dashboard.php` | **Working** |
| 18 | Admin job list with search, status filter and status-change modal | admin | `legacy/admin/jobs.php` | **Working** |
| 19 | Assign a mechanic to a repair | admin | `legacy/admin/assign-mechanic.php` | **Working** |
| 20 | Inventory CRUD (add / edit / delete spare parts) | admin | `legacy/admin/inventory.php` | **Working** |
| 21 | Mechanic CRUD + workload meter + status badge | admin | `legacy/admin/mechanics.php` | **Working** |
| 22 | Customer list, search, delete (blocked if the customer has bookings) | admin | `legacy/admin/customers.php` | **Working** |
| 23 | Customer detail page + booking history | admin | `legacy/admin/customer-details.php` | **Working** |
| 24 | Mechanic performance page (jobs, earnings, average completion days) | admin | `legacy/admin/mechanic-performance.php` | **Working** |
| 25 | Repair cost sheet: add parts (single and multiple), remove parts, stock deduction/restore, labour cost, live totals | admin | `legacy/admin/repair-details.php` | **Working** (AJAX via `fetch()`) |
| 26 | Reports: date range, revenue by day, service mix, inventory pie, mechanic table, top customers, popular parts | admin | `legacy/admin/reports.php` | **Working** (top-customer table is empty for seeded data — see §8) |
| 27 | Shared dark theme + responsive sidebar | all | `public/assets/css/portal.css` | **Working** |
| 28 | Printable invoice | admin/customer | `legacy/admin/generate-invoice.php` | **Broken** — wrong `require` path (`Fatal error`) *and* all links point to a non-existent `/generate-invoice.php` |
| 29 | Booking-confirmation e-mail | customer | `sendBookingConfirmation()` in `legacy/send-notification.php` | **Partial** — the function exists but is **never called**; the booking form still promises the e-mail |
| 30 | Avatar dropdown menu in the top bar | customer | `home.php` `toggleDropdown()` | **Partial** — JS function exists, but no `#userDropdown` element exists, so clicking the avatar throws a JS error |
| 31 | Shared helper script `main.js` | all | `public/assets/js/main.js` | **Not used** — no page includes it (dead asset) |
| 32 | Laravel-native domain layer (models, migrations, controllers, Blade views, validation, policies) | — | `app/`, `routes/web.php`, `database/migrations/` | **Not started** — only Laravel defaults exist |
| 33 | Automated tests | — | `tests/` | **Not started** — only the 2 generated placeholders |
| 34 | `notifications` table (claimed in `legacy/README.md`) | — | — | **Not started** — it does not exist in `motorcycle_repair_shop.sql` |

### 2.2 Development status summary

| Module | Progress | Comment for the presentation |
|---|---|---|
| Customer portal | ~90% | All four pages work; only *cancel booking* is broken |
| Mechanic portal | ~95% | All three pages work and are the most polished part of the system |
| Admin portal | ~90% | 11 pages all functional; invoice + a few table-styling gaps remain |
| Database | 100% | 5 tables, foreign keys, indexes and sample data are all in place |
| Laravel migration | ~10% | Laravel is wired up and routes traffic, but no domain code has been ported yet |
| Testing / QA | ~5% | Manual testing only; the Pest suite is untouched |
| Documentation | ~70% | `legacy/README.md` is good but has several inaccuracies (listed in §8) |

> Present this as an honest estimate, not a fixed fact — say "*based on the code we reviewed*".

---

## 3. Frontend Components and Visual Design

### 3.1 Page inventory (every page that exists)

All portal pages share the same shell (sidebar + top navbar + content area) and the same stylesheet.

| # | Page | URL as served by Laravel | Portal | Purpose |
|---|---|---|---|---|
| 1 | `login.php` | `/login.php` | public | Login **and** Register in two Bootstrap tabs, plus the Forgot-Password modal |
| 2 | `register.php` | `/register.php` | public | Full registration page with a live password-strength meter |
| 3 | `reset-password.php` | `/reset-password.php` | public | Sets a new password from an e-mailed token |
| 4 | `logout.php` | `/logout.php` | public | Destroys the session and redirects to `/login.php?logout=success` |
| 5 | `process-booking.php` | `/process-booking.php` | public | Receives the booking form (no UI — redirects to Track Status) |
| 6 | `send-notification.php` | `/send-notification.php` | public | PHPMailer helper functions (no UI) |
| 7 | `customer/home.php` | `/customer/home.php` | customer | Landing page: hero banner, 6 service cards, "Why choose us", opening hours, footer |
| 8 | `customer/book-service.php` | `/customer/book-service.php` | customer | Booking form (8 fields) |
| 9 | `customer/track-status.php` | `/customer/track-status.php` | customer | Booking-ID search + progress bar + 4-step timeline + cost summary + Invoice button |
| 10 | `customer/booking-status.php` | `/customer/booking-status.php` | customer | 4 stat cards, 6 filter chips, accordion booking cards with parts table and cost summary |
| 11 | `mechanic/dashboard.php` | `/mechanic/dashboard.php` | mechanic | 3 stat cards, assigned-repairs list with "Work" buttons, doughnut "My Performance" chart, "Recently Completed" list |
| 12 | `mechanic/my-repairs.php` | `/mechanic/my-repairs.php` | mechanic | Search box + status dropdown + 8-column table |
| 13 | `mechanic/work-on-repair.php` | `/mechanic/work-on-repair.php` | mechanic | Breadcrumb, Repair Info card, Update Status card, Repair Notes card, Parts Used table, cost box, Quick Tips |
| 14 | `admin/dashboard.php` | `/admin/dashboard.php` | admin | 4 KPI cards, Recent Bookings table, 7-day Revenue line chart, low-stock alert |
| 15 | `admin/jobs.php` | `/admin/jobs.php` | admin | Search + filter + job table with view/edit/wrench buttons + status modal |
| 16 | `admin/assign-mechanic.php` | `/admin/assign-mechanic.php` | admin | 3 stat cards + pending-repairs table with an inline mechanic dropdown |
| 17 | `admin/inventory.php` | `/admin/inventory.php` | admin | Add/Edit Part form on the left, parts table on the right |
| 18 | `admin/mechanics.php` | `/admin/mechanics.php` | admin | Add-mechanic form, edit banner, mechanics table with workload bars |
| 19 | `admin/customers.php` | `/admin/customers.php` | admin | 3 stat cards + searchable customer table |
| 20 | `admin/customer-details.php` | `/admin/customer-details.php` | admin | Customer profile card, 3 stat cards, booking-history table |
| 21 | `admin/mechanic-performance.php` | `/admin/mechanic-performance.php` | admin | 6 performance stat cards + recent-jobs table |
| 22 | `admin/repair-details.php` | `/admin/repair-details.php` | admin | **The richest page.** Booking info, multi-part selector, parts table, labour-cost editor with live totals, status card, toast notifications |
| 23 | `admin/reports.php` | `/admin/reports.php` | admin | Date-range filter bar + summary cards + 3 Chart.js charts + 4 data tables |
| 24 | `admin/generate-invoice.php` | `/admin/generate-invoice.php` | admin | Printable A4-style invoice (currently crashes — §8) |

### 3.2 The layout shell

Every portal page repeats the same three-part shell:

1. **Fixed left sidebar (280 px)** — a `linear-gradient` dark panel containing the brand block
   (motorcycle icon + "MotoRepair Pro" + the portal name) and a menu of `<a class="menu-item">` links.
   The current page gets `class="menu-item active"`, which paints it orange.
   When a user is logged in, extra links appear ("My Bookings", "Logout"); otherwise a single
   "Login / Register" link is shown (see the `<?php if(isset($_SESSION['user_id'])): ?>` block in every page).
2. **Sticky top navbar** — a `.page-title` on the left (icon + page name) and `.user-info` on the right
   ("Welcome, *name*" plus a circular `.user-avatar` showing the first letter of the name,
   or `G` for a guest).
3. **Content area** (`.main-content`) — Bootstrap containers/cards holding the actual page widgets,
   sometimes ending with a `.footer`.

A subtle but important layout detail: `.main-content` uses `margin-left: 280px` to sit beside the sidebar,
and the whole stylesheet uses the CSS variables `--primary-color`, `--surface`, `--text`, etc., so all three
portals automatically share one visual identity.

### 3.3 Visual design

**Design tokens** — defined once in `:root` at the top of `public/assets/css/portal.css`:

| Token | Value | Used for |
|---|---|---|
| `--primary-color` | `#e67e22` (orange) | buttons, links, active menu item, chart line, progress bars |
| `--primary-dark` | `#c8641a` | button hover state |
| `--ink` | `#16171a` | page background (very dark grey) |
| `--surface` | `#202227` | cards and panels |
| `--surface-raised` | `#2a2c33` | card headers, inputs, table headers |
| `--text` | `#edeff1` | body text (near-white) |
| `--text-muted` | `#9a9ea5` | labels, helper text, table headings |
| `--border-steel` | `rgba(207,211,216,0.12)` | 1 px card/table/input borders |

**Why orange + dark?** Orange is the "workshop / safety / motorcycle" colour and it reads well on dark
panels; the dark surface (instead of the usual white admin theme) keeps the dashboard comfortable to look at
in a workshop. The `role-badge` in the bottom-right corner of the login page even calls the system
"Customer / Admin / Mechanic Portal".

**Typography** — a single system font stack, `'Segoe UI', Tahoma, Geneva, Verdana, sans-serif`, applied to
`body`. Headings are Bootstrap sizes: page titles `.page-title` = `1.2rem/600`, KPI numbers are `h2`
inside `.stat-card`, the hero heading is `2.5rem` (dropping to `1.8rem` under 768 px).

**Spacing & shape** — `border-radius` 6–8 px for buttons and inputs, 10–12 px for cards and panels, 15 px for
the booking/track cards and the login card, and 20 px for status pill badges. `box-shadow` is used to lift
cards off the dark background (`0 2px 10px rgba(0,0,0,.2)` normally, `0 5px 20px` on hover).

**Motion** — hover lifts (`.stat-card:hover`, `.service-card:hover`, `.btn-submit:hover` use
`transform: translateY(-3px/-5px)`) and a `0.3s` transition on nearly everything. Toasts use a
`slideIn` keyframe animation. Nothing depends on animation to be usable.

### 3.4 Reusable UI components

| Component | CSS class | Where it appears | What it looks like |
|---|---|---|---|
| Stat / KPI card | `.stat-card`, `.stat-icon` | every dashboard | Dark card, small grey label, big number, tinted rounded icon |
| Content card | `.card-custom` | admin pages | Panel with an optional `.card-header` strip |
| Table container | `.table-container`, `.table-custom` | admin lists | Rounded panel; uppercase grey column headings; row hover highlight |
| Info panel | `.info-card` | mechanic work page, admin repair details | Panel with an optional coloured `.info-card-header` |
| Booking card (accordion) | `.booking-card[data-status]`, `.booking-header`, `.booking-body.show` | customer My Bookings | Clickable header that expands to show details |
| Form controls | `.form-control`, `.form-select`, `.form-control-dark` | all forms | Dark inputs with orange focus ring and a light SVG dropdown arrow (added because Bootstrap's default arrow is invisible on dark inputs) |
| Buttons | `.btn-primary-custom`, `.btn-secondary-custom`, `.btn-sm-custom`, `.btn-work`, `.btn-assign`, `.btn-add-multiple`, `.btn-update`, `.btn-remove-part`, `.btn-sm-disabled` … | everywhere | Solid orange / grey / green / yellow / red, 6–8 px radius |
| Status pills | `.status-badge` + `.status-pending / -accepted / -in_progress / -completed / -cancelled` | every list | Translucent coloured capsule, e.g. blue-tinted "In Progress" |
| Filter chips | `.filter-btn` / `.filter-btn.active` | customer My Bookings | Rounded toggle chips that filter cards with JavaScript |
| Progress bars | `.progress`, `.progress-custom`, `.track-card .progress` | tracking, mechanic, admin | 4–30 px bars; the tracking page uses a thick 30 px labelled bar |
| Timeline | `.status-timeline`, `.timeline-item`, `.timeline-dot` (=`.completed`/`.active`) | customer Track Status | Vertical line with 4 dots that turn green as the repair advances |
| Modal | Bootstrap modal + `.modal-content-custom` | login (forgot password), admin jobs (status) | Dark modal with inverted close button |
| Toast notification | `.notification-toast` + `showNotification()` | admin repair details | Slides in from the top-right and disappears after 3 s |
| Empty state | inline | every list | Centred grey icon + message, e.g. "No repairs assigned yet" |
| Avatars | `.user-avatar`, `.customer-avatar`, `.mechanic-avatar`, `.customer-avatar-large` | top bar, tables, detail pages | Orange gradient circle with the first letter of the name |

### 3.5 Icons, images and data visualisation

* **Icons:** Font Awesome 6.0.0 from `cdnjs`. Every label, button, menu item, stat card and empty state has an
  icon — `fa-motorcycle` for the brand, `fa-tachometer-alt` for dashboards, `fa-wrench` / `fa-tools` for
  repairs, `fa-boxes` for inventory, `fa-chart-bar` for reports, `fa-file-invoice` for invoices.
* **Images:** exactly **one** image is used anywhere in the UI:
  `public/assets/pic/Screenshot 2026-07-20 154440.png`, set as a repeating tile background on the login page
  with a translucent dark overlay (`body::before`) so the form stays readable.
* **Charts (Chart.js):**
  * admin dashboard — **line chart**, revenue for the last 7 days, orange line with a filled area;
  * mechanic dashboard — **doughnut chart**, Ongoing / Completed / Pending;
  * reports page — **line chart** (revenue by day), **doughnut chart** (bookings by service type) and
    **pie chart** (inventory by stock level).
  All three charts are themed for dark mode by passing `#edeff1` / `#9a9ea5` tick and legend colours.
* **Hero decoration:** the customer hero uses a CSS-only checkered "safety stripe" along its bottom edge
  (`.hero-section::after` with a repeating gradient) — a nice example of decoration without images.

### 3.6 Responsive behaviour

The layout is responsive through Bootstrap's grid (`col-md-4`, `col-lg-7`, …) plus two breakpoints in
`portal.css`:

* **≤ 768 px** — the sidebar is pushed off-screen (`left: -280px`); a fixed orange hamburger button
  (`.menu-toggle`) appears at the top-left and toggles `.sidebar.active` to slide it in. `.main-content`
  loses its 280 px left margin, the top navbar gets extra left padding so the title does not sit under the
  button, and tables become horizontally scrollable (`.table-responsive-custom`). The hero font shrinks.
  On the login page the card margin tightens.
* **≥ 769 px** — the hamburger is hidden (`display: none`) and the sidebar is always visible.

Clicking anywhere outside the sidebar on a small screen closes it again — that behaviour is repeated in a
small inline `<script>` at the bottom of each page.

### 3.7 External (CDN) dependencies

The portal pages load three CDN resources. **This means the demo machine needs internet access**, which is
worth checking before recording:

| Resource | Version | Used by |
|---|---|---|
| Bootstrap CSS + JS bundle | 5.1.3 | all pages |
| Font Awesome | 6.0.0 | all pages |
| Chart.js | latest (unpinned) | 3 dashboard/report pages |

There is **no** Tailwind/Bootstrap build step for the portals — the only Vite build in the repo
(`public/build/assets/app-*.css`, `app-*.js`) belongs to the unused Laravel welcome page.

---

## 4. Backend Architecture and Code Explanation

### 4.1 Two layers, one application

The most important technical idea in this project — and the one Member 4 must explain clearly — is that
**there are two code layers, and only one of them contains the real system.**

| Layer | Folder | What it contains | Is it "the system"? |
|---|---|---|---|
| **Wrapper** | `routes/`, `app/`, `bootstrap/`, `resources/`, `public/index.php` | A stock Laravel 12 skeleton whose only custom code is 14 routes that require the legacy PHP files | No — it just receives the URL and hands it to layer 2 |
| **Application** | `legacy/` | 27 PHP pages + `bootstrap.php` + `config/database.php` + `includes/auth.php` + PHPMailer | **Yes** — this is the working system |

### 4.2 The route table (exactly what `php artisan route:list` reports)

| Method | URI | Route handler | Purpose |
|---|---|---|---|
| GET/HEAD | `/` | `routes/web.php:29` | runs `legacy/index.php` -> redirects to `login.php` |
| GET/HEAD | `/index.php` | `web.php:30` | same landing redirect |
| GET/POST | `/login.php` | `web.php:32` | login + register + forgot-password handler |
| GET/POST | `/register.php` | `web.php:33` | full registration page |
| GET | `/logout.php` | `web.php:34` | logout |
| GET/POST | `/process-booking.php` | `web.php:35` | booking insert |
| GET/POST | `/send-notification.php` | `web.php:36` | PHPMailer library loader |
| ANY | `/admin/{page}` and `/admin/{page}.php` | `web.php:38,42` | any admin page, e.g. `/admin/jobs.php` |
| ANY | `/customer/{page}` and `/customer/{page}.php` | `web.php:46,50` | any customer page |
| ANY | `/mechanic/{page}` and `/mechanic/{page}.php` | `web.php:54,58` | any mechanic page |
| ANY | `/{path?}` (where `path` = `.*`) | `web.php:62` | catch-all: serves any other `*.php` file from `legacy/`, otherwise 404 |
| GET/HEAD | `/up` | Laravel framework | framework health endpoint |

### 4.3 How a request is processed — the wrapper

`routes/web.php` defines two small helper functions and then one route per page:

```php
// routes/web.php (lines 5-18)
function runLegacy(string $relativePath): void
{
    $file = base_path('legacy/' . $relativePath);

    if (!file_exists($file)) {
        abort(404);
    }

    chdir(dirname($file));   // move the working directory to the page's folder
    require $file;           // execute the legacy page
    exit;                    // stop Laravel from rendering anything else
}
```

In plain English, step by step, for a browser request to `http://localhost:8000/admin/jobs.php`:

1. Apache / `artisan serve` sends every request to `public/index.php` (Laravel's front controller).
2. Laravel boots, matches the URL against `routes/web.php`, and hits `Route::any('/admin/{page}.php')`.
3. That route calls `runLegacyFolder('admin', 'jobs')`, which validates the page name with a regular
   expression (`/^[a-z0-9\-]+$/`) and blocks path-traversal attempts.
4. `runLegacy()` builds the absolute path `legacy/admin/jobs.php`, checks it exists (otherwise 404),
   `chdir()`s into `legacy/admin/`, and `require`s the file.
5. `legacy/admin/jobs.php` includes `bootstrap.php`, which loads Composer's autoloader, loads
   `config/database.php` and calls `session_start()`.
6. The page's own PHP runs: it calls `requireAdmin()`, queries MySQL with mysqli, and prints HTML.
7. `exit` stops execution, so Laravel never renders a Blade view. The legacy page's HTML *is* the response.

Two consequences worth stating during the presentation:

* `chdir()` is why some legacy pages use **relative** includes such as `require_once 'config/database.php'`.
  That only resolves when the working directory is the file's own folder. It works for the pages at
  `legacy/` root, but it **breaks** `legacy/admin/generate-invoice.php` (see §8.3).
* Because the legacy pages call `exit`, Laravel's Blade views, Eloquent models, validation, policies and
  middleware *never* touch the shop data. Laravel only provides the URL, the autoloader and the CSRF bypass.

### 4.4 The bootstrap / include chain

```
legacy/index.php, login.php, register.php, logout.php, process-booking.php,
reset-password.php, admin/*.php, customer/*.php, mechanic/*.php
        |
        v
legacy/bootstrap.php
        |- define('LEGACY_ROOT', __DIR__)
        |- define('PROJECT_ROOT', dirname(__DIR__))
        |- require_once PROJECT_ROOT . '/vendor/autoload.php'     <- Composer autoloader
        |- require_once LEGACY_ROOT  . '/config/database.php'
        `- session_start()   (only if no session is active yet)

legacy/config/database.php
        |- define DB_HOST / DB_USER / DB_PASS / DB_NAME
        |- function getConnection(): mysqli
        |- session_start()
        `- "remember me": if the session is empty but a remember_token cookie
           exists, look the user up and rebuild the session

legacy/includes/auth.php   (required by guarded pages)
        |- isLoggedIn() / isAdmin() / isMechanic() / isCustomer()
        |- requireLogin() / requireAdmin() / requireMechanic()
        |- generateBookingId()  ->  'BK' + Ymd + rand(1000,9999)
        `- getQueueNumber($conn) -> MAX(queue_no) for today + 1
```

### 4.5 Database connection

`legacy/config/database.php` opens the connection with **mysqli** and returns it to the caller:

```php
// legacy/config/database.php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'motorcycle_repair_shop');

function getConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}
```

Simple, but note for the "limitations" section: the credentials are hard-coded (no `.env`), there is no
try/catch, and the default XAMPP `root` account has an empty password. On a classroom demo machine that is
fine; on real hosting it must be replaced with a least-privilege MySQL user.

### 4.6 Authentication and authorization

**Login flow** (`legacy/login.php`, lines 26–64):

1. Trim the e-mail, keep the password, read the "remember me" checkbox.
2. Reject empty input with the message *"Please enter both email and password"*.
3. Prepare `SELECT * FROM users WHERE email = ?` and bind the e-mail as a string — a prepared statement, so
   the e-mail cannot be used for SQL injection.
4. Verify the password with `password_verify($password, $user['password'])` against the bcrypt hash stored in
   `users.password`.
5. On success, copy four values into the session: `user_id`, `user_name`, `user_email` and **`role`**.
6. If "remember me" was ticked, generate a 32-byte random token, store it in a 7-day cookie **and** in
   `users.remember_token`, so the token can be validated on later visits.
7. Redirect by role: `admin/dashboard.php` for admin, `mechanic/dashboard.php` for mechanic,
   `customer/home.php` for everyone else.

**Password storage:** new accounts are created with `password_hash($password, PASSWORD_DEFAULT)`, which in
PHP 8.2 produces a bcrypt hash. That is why the raw dump shows `$2y$10$...` strings and never plain
passwords for real users.

**Authorization:** every protected page calls a guard from `legacy/includes/auth.php` immediately after the
includes. For example `legacy/admin/dashboard.php` line 6 calls `requireAdmin()`, and
`legacy/customer/booking-status.php` line 5 calls `requireLogin()`:

```php
// legacy/includes/auth.php (lines 10-34)
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ../login.php');
        exit();
    }
}
```

This is **role-based access control implemented with a session flag**. It is simple and it does stop a
customer from opening an admin URL, but it is not as strong as middleware: the check is copy-pasted into
every page, and two mechanic pages redirect to a path that does not exist (see §8.3).

**Forgot password** (`legacy/login.php`, lines 118–152 plus `legacy/reset-password.php`): a 64-character
random token is written to `users.reset_token` while MySQL itself computes the expiry with
`DATE_ADD(NOW(), INTERVAL 1 HOUR)` (so PHP and MySQL can never disagree about the time zone), then a
PHPMailer e-mail is sent with a link to `/reset-password.php?token=...`. The reset page validates the token
**and** `reset_expiry > NOW()`, requires two matching passwords of at least 6 characters, then saves a new
hash and clears the token.

### 4.7 Validation and input handling

Because this is procedural PHP, validation is written by hand inside each page. The table below shows the
validation that actually exists:

| Operation | File | Validation performed |
|---|---|---|
| Register (customer) | `legacy/register.php` lines 31–52 | name/e-mail/contact required, `filter_var(...,FILTER_VALIDATE_EMAIL)`, password >= 6 chars, password === confirm, terms accepted, e-mail uniqueness check |
| Login | `legacy/login.php` lines 22–24, 38 | both fields non-empty, `password_verify()` |
| Forgot password | `legacy/login.php` lines 118–131 | e-mail non-empty, user must exist |
| Reset password | `legacy/reset-password.php` lines 26–30 | token exists and is not expired, password >= 6 chars, passwords match |
| Book a service | `legacy/customer/book-service.php` + `process-booking.php` | **browser-side only** (`required` attributes and `min="today"` on the date); the server trusts `$_POST` |
| Update repair status | `legacy/mechanic/work-on-repair.php` line 40 | whitelist check: `in_array($new_status, ['pending','in_progress','completed'], true)` |
| Update booking status | `legacy/admin/jobs.php` line 13 | whitelist check against 5 allowed statuses |
| Add/edit part | `legacy/admin/inventory.php` | HTML `required`, `min="0"`, `step="0.01"` only |
| Add/edit mechanic | `legacy/admin/mechanics.php` | HTML `required`; password required for new records |

**Safe vs. unsafe queries.** Most data-changing code uses prepared statements, which is the correct
practice. A few read queries are still built by string interpolation, which is the main security weakness
of the project:

* `legacy/admin/jobs.php` lines 62–67 — the search term and status are pasted straight into the SQL string;
* `legacy/admin/customers.php` lines 33–37 — the same pattern for its search box;
* `legacy/admin/inventory.php` line 80 and `legacy/admin/mechanics.php` line 106 — `WHERE id = $id` without a
  cast or a binding;
* `legacy/mechanic/my-repairs.php` lines 60–61 — count queries with `$mechanic_id` interpolated (low risk,
  because the value comes from the session).

Output escaping is done with `htmlspecialchars()` throughout the HTML, which covers the XSS side.

### 4.8 Business logic worth highlighting

**a) Booking ID and queue number** (`legacy/includes/auth.php`):

```php
function generateBookingId() {
    return 'BK' . date('Ymd') . rand(1000, 9999);
}

function getQueueNumber($conn) {
    $date = date('Y-m-d');
    $sql = "SELECT MAX(queue_no) as max_queue FROM bookings WHERE DATE(created_at) = ?";
    ...
    return ($row['max_queue'] ?? 0) + 1;
}
```

The Booking ID is a human-readable reference the customer can type on the tracking page, and the queue
number restarts every day so the shop can call "queue 3 today". The tracking progress bar maps status to a
percentage: pending 25 %, accepted 50 %, in progress 75 %, completed 100 %.

**b) Status synchronisation between `bookings` and `repairs`.** The shop has two status columns — one on the
booking (what the customer sees) and one on the repair job (what the mechanic works with). They have to stay
in step, and the code does that explicitly:

| Action | Where | What happens |
|---|---|---|
| Mechanic sets a repair to *in progress* / *completed* | `legacy/mechanic/work-on-repair.php` lines 47–79 | Inside a **transaction**: update every `repairs` row for that booking, set `start_date`/`completion_date`, then set the matching `bookings.status`, then recompute `bookings.estimated_cost`. `commit()` on success, `rollback()` if anything throws |
| Admin changes the status from the job list | `legacy/admin/jobs.php` lines 16–52 | Transaction; for *in progress* it **checks first** whether a `repairs` row already exists and inserts only if it does not (an earlier version used `ON DUPLICATE KEY`, which never fired because there is no unique key, so every click created a duplicate job row) |
| Admin assigns a mechanic | `legacy/admin/assign-mechanic.php` lines 17–24 | Sets `repairs.mechanic_id`, `start_date = CURDATE()`, `repairs.status = 'in_progress'`, then moves the booking to *in progress* |

**c) Cost calculation.** The money rules are **parts cost = SUM(quantity x price_at_time)** and
**total = parts cost + labour cost**. Three places compute it consistently:

* `legacy/admin/repair-details.php` — `getPartsTotal()`, `getLaborCost()` and `updateTotalCost()` write the
  result into `bookings.estimated_cost`;
* `legacy/mechanic/work-on-repair.php` lines 66–73 — the same sum inside the status transaction;
* the customer pages (`track-status.php`, `booking-status.php`) recompute it for display with a sub-query.

Storing `price_at_time` on each `repair_parts` row is deliberate: if the shop later changes a part's price,
old invoices still show what the customer actually paid.

**d) Inventory movement.** `legacy/admin/repair-details.php` is the only place where stock changes:

* adding a part checks `stock_quantity >= quantity`, inserts into `repair_parts` with the current
  `unit_price`, then **subtracts** the quantity from `parts.stock_quantity` (lines 40–72);
* removing a part **adds the quantity back** to stock and then deletes the `repair_parts` row (lines 138–158);
* if stock is insufficient the part is skipped and the error is returned to the browser as JSON.

### 4.9 Front-end to back-end communication patterns

The system uses three different patterns, and Member 3 should point out all three on screen:

| Pattern | Used by | How it works |
|---|---|---|
| Classic **full-page POST then redirect** | login, register, booking, inventory, mechanics, status updates | `<form method="POST">` -> the same PHP file handles `$_POST` -> `header('Location: ...')` or `echo "<script>...location.href..."` |
| **GET query parameters** for filters/search | customer tracking, mechanic my-repairs, admin jobs/customers/reports | the form uses `method="GET"`, so the URL becomes e.g. `jobs.php?search=honda&status=pending` and PHP reads `$_GET` |
| **AJAX with `fetch()` returning JSON** | admin repair details | the only asynchronous code in the project: adding multiple parts and updating labour cost post form-encoded data and read `json_encode()` output back to update the totals live, with no page reload (`repair-details.php` lines 570–605) |

`public/assets/js/main.js` contains three more helpers (auto-hiding alerts, required-field highlighting and a
low-stock colour helper), but **no page loads that file**, so treat it as a planned utility rather than a
working feature.

### 4.10 E-mail notifications

`legacy/send-notification.php` bundles PHPMailer manually inside the repo and defines three functions:

| Function | Purpose | Actually called? |
|---|---|---|
| `sendEmailNotification($to, $subject, $message)` | shared SMTP sender (Gmail SMTP, STARTTLS, port 587) | yes — used by the other two |
| `sendPasswordReset($email, $name, $reset_link)` | password-reset e-mail with an orange button | **yes**, from `login.php` line 146 |
| `sendBookingConfirmation($email, $name, $booking_id, $queue_no)` | booking-confirmation e-mail | **no — never called anywhere** |

That last row is an honest gap: the booking form tells the customer *"You will receive a confirmation email
with your booking ID and queue number after submission"*, but `process-booking.php` never calls the
notification function. The customer still sees the Booking ID and queue number on the tracking page, so the
feature is only *partly* missing.

> **Critical security point for the presentation:** `send-notification.php` contains a **hard-coded Gmail
> address and app password** in clear text. Do **not** open that file on screen during the recording, and
> never show those lines in a screenshot. Revoke/rotate that app password after the demo and move the
> credentials into `.env`.

### 4.11 The dormant Laravel layer

For completeness, so nobody is surprised by questions:

| Laravel piece | State |
|---|---|
| `app/Http/Controllers/Controller.php` | empty abstract base class — **no controller exists** for any shop feature |
| `app/Models/User.php` | the default Laravel `User` model (name / e-mail / password only) — it has **no** `role`, `contact_number`, `address` or `specialization`, and no `Booking` / `Repair` / `Part` models exist |
| `database/migrations/` | only Laravel's own tables: `users`, `password_reset_tokens`, `sessions`, `cache`, `jobs`. **No migration exists for `bookings`, `repairs`, `parts` or `repair_parts`** |
| `database/seeders/DatabaseSeeder.php` | creates one demo user, `test@example.com` (unrelated to MotoRepair Pro) |
| `resources/views/welcome.blade.php` | the stock Laravel welcome page — **never rendered**, because `/` is routed to `legacy/index.php` |
| `resources/css/app.css`, `resources/js/app.js`, `public/build/*` | Tailwind 4 + Vite build, **unused** by the portals |
| `bootstrap/app.php` | the only meaningful Laravel customisation: CSRF protection is **explicitly disabled** for `login.php`, `register.php`, `logout.php`, `process-booking.php`, `send-notification.php`, `admin/*`, `customer/*`, `mechanic/*`, `reset-password.php` — necessary because the legacy forms do not send a CSRF token |
| `tests/Feature/ExampleTest.php`, `tests/Unit/ExampleTest.php` | two generated placeholders (one asserts a 200 on `/`, one asserts `true === true`) |
| `.env` | `APP_NAME=Laravel` (not rebranded), `APP_URL=http://localhost:8000`, `DB_CONNECTION=mysql`, `DB_DATABASE` is defined twice (first `laravel`, then `motorcycle_repair_shop` — the second value wins and is what Laravel resolves to), `SESSION_DRIVER=file`, `CACHE_STORE=database`, `MAIL_MAILER=log` (the Laravel mailer is **not** used — e-mail goes through PHPMailer) |

**Guidance for the presentation:** describe this layer as *"the migration target"*. The team has not yet
moved the shop data into Eloquent models, controllers or Blade views; that is the largest item on the
remaining-work list in Section 8.
---

## 5. Database Structure and Integration

### 5.1 Connection summary

| Item | Value |
|---|---|
| Engine | MySQL / MariaDB (mysqli driver) |
| Host / port | `localhost` / `3306` |
| Database name | `motorcycle_repair_shop` |
| User / password | `root` / (empty) — default XAMPP |
| Character set | `utf8mb4` / `utf8mb4_general_ci` |
| Schema file | `legacy/motorcycle_repair_shop.sql` (phpMyAdmin export, MariaDB 10.4.32) |
| Tables | **5** — `users`, `bookings`, `repairs`, `parts`, `repair_parts` |

### 5.2 Entity relationships

```
   users (1) --------< (0..n) bookings        bookings.user_id  -> users.id    ON DELETE SET NULL
     |                            |
     |                            | (1)
     |                            v
     |                        (0..n) repairs   repairs.booking_id -> bookings.id ON DELETE CASCADE
     |                            |
     | (1)                        | (1)
     +------------< (0..n) repairs|            repairs.mechanic_id -> users.id  ON DELETE SET NULL
                                  |
                                  | (1)
                                  v
                             (0..n) repair_parts  repair_parts.repair_id -> repairs.id ON DELETE CASCADE
                                        |         repair_parts.part_id    -> parts.id  (no cascade)
                                        | (0..n)
                                        v
                                      (1) parts
```

### 5.3 Table `users` — everybody in the system

One table holds all three roles; the `role` column decides what the user can do.

| Column | Type | Notes |
|---|---|---|
| `id` | `int(11)` PK, AUTO_INCREMENT | |
| `name` | `varchar(100)` | |
| `email` | `varchar(100)` **UNIQUE** | used as the login username |
| `password` | `varchar(255)` | bcrypt hash from `password_hash()` |
| `contact_number` | `varchar(20)` | nullable |
| `address` | `text` | used by the customer detail pages |
| `role` | `enum('customer','admin','mechanic')` | default `customer` |
| `remember_token` | `varchar(255)` | 7-day "remember me" token |
| `reset_token` | `varchar(255)` | password-reset token |
| `reset_expiry` | `datetime` | token expiry (1 hour) |
| `created_at` | `timestamp` | default `CURRENT_TIMESTAMP` |
| `specialization` | `varchar(100)` | default `General` — used for mechanics only |

### 5.4 Table `bookings` — the customer's request

| Column | Type | Notes |
|---|---|---|
| `id` | `int(11)` PK | internal id, referenced by `repairs.booking_id` |
| `booking_id` | `varchar(20)` **UNIQUE** | the public code, e.g. `BK202412010001` |
| `user_id` | `int(11)` NULL FK -> `users.id` | **NULL for guest bookings** |
| `full_name`, `contact_number`, `motorcycle_model` | varchar | copied from the form so a guest booking still carries contact details |
| `problem_description` | `text` | |
| `service_type` | `varchar(50)` | one of the six services in the dropdown |
| `preferred_date` | `date` | |
| `queue_no` | `int(11)` | daily queue position |
| `status` | `enum('pending','accepted','in_progress','completed','cancelled')` | default `pending` — **this is the status the customer sees** |
| `estimated_cost` | `decimal(10,2)` | default `0.00`; kept up to date as parts and labour are recorded |
| `created_at` / `updated_at` | `timestamp` | `created_at` defines "today" for the queue number |

Indexes: PRIMARY (`id`), UNIQUE (`booking_id`), KEY (`user_id`), plus
`bookings_ibfk_1` FK to `users(id) ON DELETE SET NULL`.

### 5.5 Table `repairs` — the mechanic's job

| Column | Type | Notes |
|---|---|---|
| `id` | `int(11)` PK | |
| `booking_id` | `int(11)` FK -> `bookings.id` | **note: this is the numeric id, not the `BK...` code** |
| `mechanic_id` | `int(11)` NULL FK -> `users.id` | NULL means "not yet assigned" |
| `status` | `enum('pending','in_progress','completed')` | the mechanic's view of the job |
| `labor_cost` | `decimal(10,2)` | entered by the admin on the repair-details page |
| `start_date` / `completion_date` | `date` | used for the average-completion-days metric |
| `notes` | `text` | append-only work log written by the mechanic |
| `created_at` | `timestamp` | |

Constraints: `repairs_ibfk_1` -> `bookings(id) ON DELETE CASCADE`,
`repairs_ibfk_2` -> `users(id) ON DELETE SET NULL`.

> **Important gotcha for Member 4 to mention.** Two different columns are both called `booking_id`:
> `bookings.booking_id` is the text code (`BK2026...`) while `repairs.booking_id` is a **number** pointing at
> `bookings.id`. A fix comment inside `legacy/mechanic/work-on-repair.php` (lines 17–20) explains that
> selecting both with `r.*` plus `b.booking_id` made the text value overwrite the numeric one, so the
> `UPDATE bookings ... WHERE id = ?` matched nothing. The page now aliases them as
> `r.booking_id AS booking_pk` and `b.booking_id AS booking_code`. This is a great example of a real bug the
> team found through testing.
>
> A second, related consequence: `repairs` has **no UNIQUE key on `booking_id`**, so one booking can
> accumulate several repair rows. The code now defends against this by always picking one row
> (`ORDER BY (mechanic_id IS NULL), id DESC LIMIT 1`) and updating **all** rows for that booking when a
> status changes.

### 5.6 Table `parts` — spare-part inventory

| Column | Type | Notes |
|---|---|---|
| `id` | `int(11)` PK | |
| `part_name` | `varchar(100)` | |
| `description` | `text` | |
| `stock_quantity` | `int(11)` | default 0; below 10 triggers the dashboard alert |
| `unit_price` | `decimal(10,2)` | copied into `repair_parts.price_at_time` when used |
| `created_at` | `timestamp` | |

There is no `updated_at` column, and no `deleted_at` (deletes are permanent).

### 5.7 Table `repair_parts` — the join table (parts used on a job)

| Column | Type | Notes |
|---|---|---|
| `id` | `int(11)` PK | |
| `repair_id` | `int(11)` FK -> `repairs.id` ON DELETE CASCADE | |
| `part_id` | `int(11)` FK -> `parts.id` | no cascade, so a part already used on a repair cannot be silently deleted |
| `quantity` | `int(11)` | |
| `price_at_time` | `decimal(10,2)` | the price captured at the moment of use — keeps old invoices accurate |
| `created_at` | `timestamp` | |

### 5.8 Sample data shipped with the schema

| Table | Rows | Notes |
|---|---|---|
| `users` | 9 | 1 admin, 2 mechanics, 6 customers (see the credential warning below) |
| `bookings` | 5 | statuses: 1 accepted, 2 in progress, 2 completed |
| `repairs` | 7 | including duplicates for booking 1 and booking 5 |
| `parts` | 10 | Engine Oil, Spark Plug, Brake Pads, Air Filter, Battery, Chain Set, Clutch Cable, Headlight Bulb, Tire Tube, Brake Fluid |
| `repair_parts` | 8 | parts consumed by repairs 1, 2 and 4 |

> **Demo-credential warning (verified with `password_verify()`):** both `admin@repairshop.com` and
> `mechanic@repairshop.com` use the password **`password`**. The `legacy/README.md` claims
> `admin123` / `mechanic123` — that is **wrong**. Also note that the customer row `jane@example.com` stores
> `$2y$10$examplehashedpassword`, which is not a valid bcrypt hash, and the row `mike@gmail.com` stores a
> plain `123`; **neither of those two accounts can log in** through `password_verify()`. Test the logins
> before recording and use an account you know works.

### 5.9 CRUD map — where each database operation happens

| Operation | Table(s) | File and function |
|---|---|---|
| **CREATE** user | `users` | `legacy/register.php` (customer self-signup); `legacy/admin/mechanics.php` (admin adds a mechanic) |
| **CREATE** booking | `bookings` | `legacy/process-booking.php` |
| **CREATE** repair job | `repairs` | `legacy/admin/repair-details.php` (creates a pending repair if none exists); `legacy/admin/jobs.php` (creates one when set to in progress); `legacy/admin/assign-mechanic.php` |
| **CREATE** part | `parts` | `legacy/admin/inventory.php` (the `save_part` branch with `part_id = 0`) |
| **CREATE** parts-used rows | `repair_parts` | `legacy/admin/repair-details.php` (single + AJAX bulk) |
| **READ** everything | all | every page; the heaviest read queries are in `legacy/admin/reports.php` |
| **UPDATE** booking status | `bookings` | `jobs.php`, `work-on-repair.php`, `assign-mechanic.php` |
| **UPDATE** repair status / notes / labour | `repairs` | `work-on-repair.php`, `jobs.php`, `repair-details.php` |
| **UPDATE** stock | `parts` | `repair-details.php` (subtract on add, restore on remove) |
| **UPDATE** booking total | `bookings.estimated_cost` | `repair-details.php` -> `updateTotalCost()`, `work-on-repair.php` |
| **UPDATE** user profile / password | `users` | `mechanics.php` (edit), `reset-password.php` (new password, clears the token) |
| **DELETE** part | `parts` | `inventory.php` (the `delete` query parameter) |
| **DELETE** mechanic | `users` | `mechanics.php` — blocked if the mechanic has assigned repairs |
| **DELETE** customer | `users` | `customers.php` — blocked if the customer has bookings |
| **DELETE** part from a repair | `repair_parts` | `repair-details.php` (the `remove_part` query parameter) |

### 5.10 Data issues observed in the sample dataset

Honest observations that Member 5 can present under "limitations":

1. **Seeded bookings have `user_id = NULL`.** Bookings 1–4 were inserted without a user, so they never
   appear in "My Bookings" (which filters on `user_id`) and the *Top Customers* report is empty for them.
   Only bookings created through the live form receive a `user_id`.
2. **Duplicate repair rows** exist for booking 1 and booking 5 (that is the bug the team fixed — see §5.5).
3. **Two accounts cannot log in** (invalid / plaintext password values, see §5.8).
4. **Currency mismatch.** All amounts are rendered with a dollar sign and the invoice uses a US placeholder
   address (`123 Repair Street, Motor City, MC 12345`, phone `(555) 123-4567`), while the customer records,
   contact numbers and addresses (e.g. "Tagudin, Ilocos Sur") are clearly Philippine. The shop currency
   should be standardised to the peso sign and the invoice address replaced with the real one.
5. `users.address` is `NULL` for most rows, so the customers table shows "N/A" or an empty address.
---

## 6. Five-Member Presentation Script

### Roles at a glance

| Member | Role title | Main question they answer | Target time |
|---|---|---|---|
| **1** | System Introduction and Overview | *What is this system, who is it for, and how far along are we?* | 3:00 |
| **2** | Frontend Design and Visual Components | *What does it look like, and why is it designed that way?* | 4:00 |
| **3** | Frontend Functionality and User Interaction | *What can a user actually do, and what happens when they click?* | 4:00 |
| **4** | Backend Code and Database | *What runs on the server, and where does the data go?* | 4:30 |
| **5** | Integration, Testing, Progress and Conclusion | *Does it all work together, what is finished, and what is next?* | 3:30 |

> **Script-writing convention used below.** The *"Say this"* blocks are written the way you would speak them
> out loud — short sentences, no code jargon. The *"Show this"* blocks tell you exactly which screen or file
> to be on. The *"If asked"* boxes give you a ready answer for the most likely instructor question.

---

### MEMBER 1 — System Introduction and Overview

**Goal:** set the scene in three minutes so that Members 2–5 can go straight into detail.

#### Show this (screen plan)

| Point in the script | What should be on screen |
|---|---|
| Opening | A title slide: **"MotoRepair Pro — Motorcycle Repair Shop Management System"**, your group name, the five member names, the date |
| Purpose | The customer home page (`http://localhost:8000/customer/home.php`) — the six service cards are a good visual |
| Target users | A simple slide with three boxes: **Customer / Mechanic / Admin**, and one line of what each can do |
| Main features | A five-item slide (booking, tracking, mechanic jobs, inventory, reports) — no screenshots needed |
| Architecture | The architecture diagram from Section 1.7 (or the equivalent slide) |
| Current stage | A one-line status slide: *"Core system working end-to-end; framework migration and testing still in progress."* |

#### Step-by-step demonstration

1. Start on the title slide. Do **not** start with the browser open — the audience needs the context first.
2. When you say "it is a web application", switch the browser tab to `/customer/home.php` and let it sit
   there while you describe the purpose. Do not click anything yet.
3. When you reach the three roles, change to the roles slide (or point at the sidebar on the customer home
   page and explain that each role gets a different menu).
4. For the architecture slide, keep your voice calm and give only the headline: *"there is a Laravel layer
   on top and the working application underneath — Member 4 will explain this in detail."*
5. Hand over on the queue "our backend developer".

#### Say this

> "Good morning, everyone. We are Group ——, and our project is called **MotoRepair Pro**, a motorcycle
> repair shop management system.
>
> Let me start with the problem. Most small motorcycle repair shops still run on paper. The job orders are on
> a notepad, the service queue is on a whiteboard, and the spare parts are counted by memory. So customers
> keep calling to ask *'is my motorcycle ready?'*, the shop cannot easily tell which service is most
> profitable, and parts run out without warning.
>
> MotoRepair Pro solves that by putting the whole service flow online. It has **three portals**. The
> **customer portal** lets a motorcycle owner book a repair, get a Booking ID and a queue number, and track
> the progress from anywhere. The **mechanic portal** shows each technician the jobs assigned to them, and
> lets them update the status and write work notes. And the **admin portal** is where the owner manages
> everything — jobs, mechanic assignment, spare-part inventory, customers, invoices and reports.
>
> To summarise the five main features: **online booking, live repair tracking, mechanic job management,
> spare-part inventory with automatic stock deduction, and management reporting.**
>
> On the technical side, our system runs on **PHP and MySQL**, with **Bootstrap** for the interface and
> **Chart.js** for the analytics charts. Above the application we have a **Laravel** layer that handles the
> URLs — Member 4 will explain how that works in detail.
>
> As for our current stage: the core workflow is already working from end to end. A customer can book, an
> admin can assign a mechanic, the mechanic can complete the job, and the customer can see it finish — all
> with real data in the database. What is still in progress is the migration of the code into Laravel, and a
> proper automated test suite. We will be honest about our remaining gaps at the end of this presentation.
>
> Next, my groupmate will take you through the user interface and the visual design of the system."

#### Technical terms in simple language (for your own understanding)

* **Portal** — a set of pages for one type of user. Three roles, three portals.
* **Booking ID** — a reference code like `BK202605041120` that identifies one customer's repair request.
* **Queue number** — the position in today's line, e.g. "queue number 3".
* **Inventory** — the list of spare parts the shop has in stock and their quantities.
* **Server-side / backend** — the part of the program that runs on the server, not in the browser.

#### If asked — "Why Laravel if the application is in plain PHP?"

> "We started with plain PHP to get the features working quickly, and we wrapped it with Laravel so we could
> route URLs and keep the option of migrating everything into Laravel's structure later. The migration is
> the next phase of our project — that is the honest current status."

#### Transition line to Member 2

> "So that is what the system does and how far we have come. Now, let me hand over to ——, who will show you
> how the interface looks and why we designed it that way."

---

### MEMBER 2 — Frontend Design and Visual Components

**Goal:** explain the look and feel, and demonstrate the shared design system. This member is mostly *showing
screens*, not reading code. Show `public/assets/css/portal.css` only briefly, at the end.

#### Show this (screen plan)

| Point in the script | What should be on screen |
|---|---|
| Login page | `/login.php` — full page, showing the background image, the orange gradient card header, the two tabs |
| Register page | `/register.php` — the full form with the password-strength meter |
| Shell / layout | `/admin/dashboard.php` — scroll slowly so the audience sees the sidebar, the top navbar and the cards |
| Design tokens | Switch to the code editor with `public/assets/css/portal.css` scrolled to the `:root` block (lines ~28–38) |
| Components | Back to the browser: dashboard → jobs table → inventory form → repair-details modal/toast |
| Responsive | Drag the browser window narrow (or press **F12 → Ctrl+Shift+M**) to show the sidebar sliding away and the hamburger button appearing |
| Colour/typography summary | A slide listing primary orange `#e67e22`, dark surfaces, and the Segoe UI font stack |

#### Step-by-step demonstration

1. Open `/login.php` first. Point out three things out loud: the motorcycle background image, the orange
   gradient header, and the **Login / Register tabs** on a single card.
2. Click the **Register** tab and then open `/register.php` to show that the full registration page exists —
   mention the password-strength meter.
3. Log in as the admin, which lands on `/admin/dashboard.php`. Pause and describe the shell:
   *"left sidebar for navigation, top bar for the page title and the user avatar, and the content area for
   the cards."* Drag the mouse along the sidebar as you list the menu items.
4. Scroll down the dashboard slowly so the KPI cards, the recent-bookings table, the revenue line chart and
   the low-stock alert all appear on screen.
5. Switch to the code editor and show the `:root` block in `portal.css`. Read out two or three variables, not
   all of them: `--primary-color: #e67e22`, `--ink: #16171a`, `--text: #edeff1`. Explain in one sentence:
   *"we defined our colours once here, so all three portals automatically look like one system."*
6. Back to the browser: open `/admin/jobs.php` to show the table styling and the status pills, then
   `/admin/inventory.php` to show the form styling, then `/admin/repair-details.php` to show the coloured
   section headers and a toast notification.
7. Finish with the responsive demonstration: narrow the window until the hamburger appears, click it, and
   point out that the sidebar slides in and closes when you click outside.

#### Say this

> "Thank you, ——. Now I will show you the **frontend design** of MotoRepair Pro.
>
> We start at the login page. Everything here is designed around one visual idea: **orange on dark**. Orange
> is our primary colour — we chose it because it is the colour of workshops, safety markings and
> motorcycles, and it stands out well on a dark background. We used a motorcycle-themed background image
> with a dark overlay so that the form stays readable.
>
> On this same page you can see our **Login and Register tabs**, so a new customer does not have to look for
> a separate page. And here on the register page we added a **password strength meter**, so the user gets
> feedback while typing instead of only seeing an error after submitting.
>
> Now, after logging in as the admin, this is the main layout. Every page in all three portals uses the same
> **three-part shell**: a **fixed sidebar** on the left for navigation, a **top navbar** with the page title
> and the user avatar, and the **content area** for the actual widgets. We did this deliberately — once a
> user learns where the menu is on one page, they know it on every page.
>
> The sidebar also changes depending on who is logged in. A customer sees *Home, Book Service, Track Status*
> and *My Bookings*; a mechanic sees *Dashboard* and *My Repairs*; and an admin sees the full management menu
> — *Job List, Assign Mechanic, Inventory, Mechanics, Customers* and *Reports*. The current page is always
> highlighted in orange.
>
> For the **colour system**, we defined our colours once as CSS variables in a single shared stylesheet
> called `portal.css`. Here they are on screen. `--primary-color` is our orange, `--ink` is the very dark
> background, `--surface` is the card background, and `--text` is the near-white text colour. Because all
> eighteen portal pages link this one file, the whole system is consistent, and if the owner ever wants a
> different brand colour, we only change one line. Our **typography** is a single font family, Segoe UI, used
> at different sizes, so there is no font mixing.
>
> Let me show you our **reusable components**. These are our **stat cards** — used on every dashboard to show
> a big number with an icon. These are our **data tables**, with uppercase grey headings and a highlight when
> you hover a row. These are our **status pills** — yellow for pending, cyan for accepted, blue for in
> progress and green for completed — so a user can read the state at a glance without reading words. Here are
> our **forms**, with a dark input background and an orange focus ring. And here on the repair-details page
> you can see our **coloured section headers** and a **toast notification** that slides in from the top-right
> and disappears by itself.
>
> Two more details. First, our **charts** are drawn with Chart.js — a line chart for revenue, a doughnut
> chart for the mechanic's workload, and pie and doughnut charts on the reports page. Second, our layout is
> **responsive**. Watch what happens when I make the window narrow: the sidebar slides away and a hamburger
> button appears, and the tables become scrollable sideways. So the system is usable on a phone, which
> matters because our customers will mostly open the tracking page on their phones.
>
> So, to sum up: one shared stylesheet, one colour system, one layout shell, and reusable components — which
> means the whole system looks and feels like one product, not three separate websites.
>
> Now my groupmate will show you what actually happens when a user interacts with all of this."

#### Technical terms in simple language

* **CSS variables (design tokens)** — named values such as `--primary-color` stored in one place and reused
  everywhere, so a colour change happens in one line instead of eighteen files.
* **Component** — a reusable piece of interface, like a card or a status pill, that is styled the same way
  wherever it is used.
* **Responsive** — the layout rearranges itself to fit small screens.
* **CDN** — a public server that hosts libraries (Bootstrap, Font Awesome, Chart.js) so the browser can
  download them. *Mention that this means the system needs internet access.*
* **Gradient** — a background that fades from one colour to another (visible on our buttons and avatar).
* **Toast** — a small message box that pops up briefly to confirm an action.

#### If asked — "Why a dark theme instead of a normal white admin panel?"

> "Mechanics use this inside a workshop, often on a phone or a shop monitor, so a dark background is easier
> on the eyes and our orange accent keeps the important actions obvious. It also gives the system a single
> strong identity across all three portals."

#### Transition line to Member 3

> "That is the design. Now —— will demonstrate how to actually use the system, starting from a brand-new
> booking."

---

### MEMBER 3 — Frontend Functionality and User Interaction

**Goal:** this is the *live demo* member. Keep a prepared "happy path" ready and narrate each step as a
cause-and-effect: *"I click here, so the system does this."*

#### Show this (screen plan)

| Point in the script | What should be on screen |
|---|---|
| Guest browsing | `/customer/home.php` scrolled to the six service cards |
| Booking form | `/customer/book-service.php` — fill it in live |
| Result of booking | `/customer/track-status.php?booking_id=...` right after submitting |
| Tracking search | `/customer/track-status.php` — type a Booking ID and press Track |
| Customer history | `/customer/booking-status.php` — show the stat cards, the filter chips and an expanded card |
| Mechanic side | `/mechanic/dashboard.php` → `/mechanic/my-repairs.php` (search) → `/mechanic/work-on-repair.php` |
| Admin side | `/admin/dashboard.php` → `/admin/jobs.php` (modal) → `/admin/repair-details.php` (AJAX totals) |
| Honest gaps | A slide listing: cancel-booking button, invoice button, guest avatar menu |

#### Step-by-step demonstration

1. **Guest browsing.** Open `/customer/home.php` without logging in. Scroll to the service cards and say:
   *"a visitor can already view our services — they do not need an account yet."*
2. **Booking.** Open `/customer/book-service.php`. Fill in realistic values:
   * Full Name: your own name
   * Contact Number: a sample number
   * Motorcycle Model: `Honda Wave 100`
   * Service Type: `Engine Repair`
   * Problem: `Hard to start and produces white smoke`
   * Preferred Date: today or tomorrow
   Then click **Submit Booking**. Point out that the app instantly redirected to the tracking page.
3. **Tracking.** On the tracking page, point out the **Booking ID**, the **Queue Number**, the progress bar
   (it will be at *Pending*), the customer details panel, the repair details panel and the four-step
   timeline. Then use the sidebar to go back to **Track Status** and re-type the same Booking ID to prove the
   lookup works from the search box. Also type a wrong ID to show the **"Booking ID not found"** message.
4. **Customer history.** Log in as a customer who has bookings, open **My Bookings**, and show:
   * the four stat cards (Total, Pending, In Progress, Completed);
   * the six filter chips — click **Completed** and show that the list filters instantly without reloading;
   * click a booking header to expand it and show the parts table, the cost summary and the progress bar.
5. **Mechanic side.** Log out, log in as a mechanic. On `/mechanic/dashboard.php` point out the three stat
   cards, the assigned-repairs list and the doughnut chart. Go to **My Repairs**, type part of a customer
   name in the search box and press **Filter** — point out that the URL changed to
   `my-repairs.php?search=...&status=all`, which is how the filter is remembered. Open a job with **Work**.
6. **Status update.** On the work page, change the status dropdown to **In Progress** and click **Update**.
   When the confirmation alert appears, explain that the database has just been updated and the customer's
   tracking page will now show a longer progress bar. Add a note in the **Repair Notes** box and click
   **Add Note** in it, then scroll to show the note with its date and time stamp.
7. **Admin side (optional, if time allows).** Log in as admin. On `/admin/jobs.php`, click the pencil icon to
   open the **status modal**. On `/admin/repair-details.php`, select a part, set the quantity, click
   **Add Selected Parts**, and point out that the totals update on screen and the stock quantity drops.
   Emphasise: *"this part used AJAX, so the page did not reload."*
8. **Honest gaps slide.** Say clearly which buttons are *not* working yet — see the script below.

#### Say this

> "Thank you, ——. Now I will show you how a real user interacts with the system. I will do this as a **live
> demo**, following one motorcycle from booking to repair.
>
> First, a visitor. Anyone can open our home page without logging in and see our six services — engine
> repair, oil change, electrical fix, brake repair, tyre change and general maintenance. If they click
> **Book a Service**, they get this form: name, contact number, motorcycle model, service type, problem
> description and preferred date. The date picker will not let you choose a date in the past.
>
> I will submit this booking now. Watch what happens — the system generates a **Booking ID**, gives it a
> **queue number** for today, saves it to the database, and redirects me straight to the tracking page. I did
> not have to type the Booking ID myself.
>
> This is the tracking page. The progress bar shows the current stage, and below it we have a four-step
> timeline — *Booking Confirmed, Job Accepted, Repair In Progress, Repair Completed*. Right now it says
> **Pending**, because the shop has not accepted the job yet. If I come back later, this page will show the
> updated stage, the parts used and the running total.
>
> The tracking page also works as a **search tool**. I can go to *Track Status* and type a Booking ID by
> hand — like this — and the system will find it. And if I type an ID that does not exist, we show a clear
> **'Booking ID not found'** message instead of an error page.
>
> Now I will log in as a customer. This is **My Bookings**. These four cards summarise my total, pending, in
> progress and completed bookings. These chips at the bottom — *All, Pending, Accepted, In Progress,
> Completed, Cancelled* — filter the list **instantly in the browser**; the page does not reload. If I click
> a booking, it expands to show the service details, the parts that were used, the cost breakdown of parts
> plus labour, and the progress. This is what makes the system useful to a customer — they can see exactly
> what they are paying for.
>
> Let me switch to the **mechanic** account. The dashboard shows three counters — ongoing, completed and
> pending — plus a doughnut chart of the same numbers. The list below shows my assigned repairs. Notice that
> a completed job has a grey **'Completed'** label instead of a **Work** button, so a mechanic cannot
> accidentally reopen a finished job.
>
> In **My Repairs** I can search by booking ID, customer name or motorcycle model, and I can filter by
> status. If I press **Filter**, look at the URL — the search text and the status are stored in the address,
> which means I can bookmark the result or send it to a colleague.
>
> Now the important part. I will click **Work** on this job. This page shows the customer's problem
> description, the parts already used and the cost. I will change the status to **In Progress** and press
> **Update**. The system saves it and shows this confirmation. Behind the scenes it updated both the repair
> record and the booking record, and it recorded today's date as the start date — so the customer's tracking
> page has just moved forward without anyone sending a message.
>
> I can also add a work note here, for example *'replaced spark plug, advised customer to return in one
> week'*. Notes are saved with the date and time and they accumulate, so this becomes a repair history. If I
> now scroll up and log in as the admin and open this same job in **Repair Details**, I can add a part, and
> the parts total, labour cost and grand total update **on the same screen without reloading** — that is our
> AJAX feature.
>
> Before I hand over, I want to be honest about three things that are **not** working yet. First, the
> **Cancel Booking** button on the customer booking card still points to a file that does not exist, so it
> currently leads to a 404 error — that is our next bug to fix. Second, the **Invoice** button opens a page
> that crashes because of a wrong file path. Third, the avatar in the top bar of the customer home page is
> supposed to open a small menu, but the markup for that menu is missing, so clicking it produces a
> JavaScript error. We are reporting these as known issues rather than hiding them.
>
> Now my groupmate will go one level deeper and show you the server-side code and the database behind all of
> this."

#### Technical terms in simple language

* **Redirect** — the server sends the browser to a different page automatically after an action.
* **AJAX / `fetch()`** — the page talks to the server in the background and updates part of the screen
  without a full reload. Our repair-cost totals use this.
* **Client-side filtering** — the filtering happens in the browser with JavaScript, so it is instant and does
  not need the server.
* **Query string** — the part of the URL after the `?`, e.g. `?search=honda&status=pending`.
* **404** — the "page not found" response.
* **Session** — the server remembering who is logged in between page loads.

#### If asked — "What is your worst bug right now?"

> "The invoice page. It has two problems: the customer and admin links point to `/generate-invoice.php`, but
> the file actually lives at `legacy/admin/generate-invoice.php`, and inside that file a relative
> `require 'config/database.php'` resolves to the wrong folder, so it stops with a fatal error. We have
> already diagnosed both causes and the fix is small — we simply ran out of time before this presentation."

#### Transition line to Member 4

> "So that is what the user experiences. But all of that only works because the server is doing the real
> work behind the screen. Let me hand over to ——, who will show you the backend code and the database."

---
### MEMBER 4 — Backend Code and Database

**Goal:** explain what happens on the server and where the data lives. This member shows **files and code
snippets**, not long browsers demos. Do not open `legacy/send-notification.php` (it contains a password).

#### Show this (screen plan)

| Point in the script | What should be on screen |
|---|---|
| Folder structure | A file explorer or the editor's file tree: `routes/`, `app/`, `public/`, `legacy/`, `database/` |
| The wrapper | Code editor: `routes/web.php` lines 1–30, with `runLegacy()` highlighted |
| Bootstrap chain | `legacy/bootstrap.php` (whole file, only 9 lines) and `legacy/config/database.php` lines 1–15 |
| Login code | `legacy/login.php` lines 26–64 (`password_verify` + session + role redirect) |
| Security guard | `legacy/includes/auth.php` lines 6–34 |
| Business logic | `legacy/includes/auth.php` lines 43–56 (`generateBookingId`, `getQueueNumber`) |
| Booking creation | `legacy/process-booking.php` (whole file, 32 lines) |
| Status sync | `legacy/mechanic/work-on-repair.php` lines 38–80 |
| Inventory + AJAX | `legacy/admin/repair-details.php` lines 40–72 and the `updateTotalCost()` function |
| Database | phpMyAdmin (or a terminal `mysql` client) with the `motorcycle_repair_shop` database, showing the five tables and one result grid |

> **Note about the recording:** if you cannot open phpMyAdmin during the demo, use a slide with a screenshot
> of the five tables and the `Structure` view of `bookings`. Prepare that screenshot in advance.

#### Step-by-step demonstration

1. Start with the **folder structure** on screen. Point out that there are two groups: the Laravel files at
   the top level, and the `legacy/` folder which holds the actual pages. Say plainly: *"the customer never
   sees folders — the browser only sees URLs."*
2. Open `routes/web.php` and highlight `runLegacy()`. Walk through it line by line — this is the single most
   important idea of the whole system and the instructor is very likely to ask about it.
3. Open `legacy/bootstrap.php` (it is only nine lines) and then `legacy/config/database.php`. Explain the
   three things that happen before any page runs: load the autoloader, connect to MySQL, start the session.
4. Open `legacy/login.php` around lines 26–64. Point at the prepared statement and at `password_verify()`,
   then at the four `$_SESSION` assignments and the role-based redirect. Do **not** read the HTML below.
5. Open `legacy/includes/auth.php` and show `isAdmin()` / `requireAdmin()` for two seconds, then scroll to
   `generateBookingId()` and `getQueueNumber()`.
6. Open `legacy/process-booking.php`. Because it is short, this is a good one to walk through completely:
   generate ID → get queue number → prepare the INSERT → bind → execute → redirect. Reference the booking
   you created in Member 3's demo and say *"this is the code that saved it."*
7. Open `legacy/mechanic/work-on-repair.php` lines 38–80 and explain `begin_transaction()` … `commit()`, and
   what the four `UPDATE` statements do. Mention the alias bug they fixed (Section 5.5) — it shows real
   debugging.
8. Open `legacy/admin/repair-details.php` and show two things: the stock check + stock subtraction, and
   `updateTotalCost()`. Explain that this is the same calculation the invoice and the customer pages use.
9. Finish in the database: open phpMyAdmin, show the five tables, then run **SELECT from bookings** ordered by
   `created_at DESC` so the booking from the demo appears at the top. Also show `repair_parts` to prove the
   parts you added were recorded.

#### Suggested code snippets and one-line explanations

| Snippet | File | Say this about it |
|---|---|---|
| `runLegacy()` | `routes/web.php` 5–18 | "This is the bridge. Laravel receives the URL, then hands the request to our existing PHP page and stops." |
| Bootstrap chain | `legacy/bootstrap.php` 1–9 | "Nine lines. Autoloader, database config, session start — that is everything every page needs." |
| `getConnection()` | `legacy/config/database.php` 7–14 | "One function opens the MySQL connection and every page calls it." |
| `password_verify()` | `legacy/login.php` 38 | "We never store real passwords, only a one-way hash, and this function compares them safely." |
| `requireAdmin()` | `legacy/includes/auth.php` 29–34 | "One line at the top of a page decides whether the user is allowed to see it." |
| `generateBookingId()` + `getQueueNumber()` | `legacy/includes/auth.php` 43–56 | "This is what creates the Booking ID and the daily queue number." |
| The whole INSERT | `legacy/process-booking.php` 19–27 | "Prepare, bind, execute, redirect. The `?` placeholders are what stop SQL injection." |
| `begin_transaction()` … `commit()` | `legacy/mechanic/work-on-repair.php` 49–74 | "Four updates that must all succeed together — if one fails we roll back so the data can never be half-updated." |
| Stock movement | `legacy/admin/repair-details.php` 55–66 | "We record the part, then subtract it from stock, so inventory stays accurate automatically." |
| `updateTotalCost()` | `legacy/admin/repair-details.php` 162–169 | "Parts plus labour equals the booking total — one function, used everywhere." |

#### Say this

> "Thank you, ——. Now I will explain what happens on the server, and where the data actually lives.
>
> First, our **folder structure**. At the top level we have the Laravel files — `routes`, `app`, `public`,
> `resources` and `database`. Inside `legacy` we have the application itself: the customer, mechanic and
> admin pages. So there are two layers, and I want to be very clear about which one does the work: the
> **`legacy` folder is the real application**. Laravel is a wrapper around it.
>
> Here is how the wrapper works. This is `routes/web.php`. Laravel receives every URL, and instead of
> rendering its own page, it calls this function, `runLegacy`. It finds the matching PHP file inside the
> legacy folder, changes the working directory to that folder, loads the file, and then stops. That is the
> whole integration. The advantage is that we could take a working PHP system and put a modern router in
> front of it without rewriting it. The honest disadvantage is that we are not yet using Laravel's models,
> controllers or Blade views — that migration is still ahead of us. That is the biggest item on our
> remaining-work list.
>
> Before any page runs, three things happen, and they are in these nine lines of `bootstrap.php`: we load the
> Composer autoloader, we load the database configuration, and we start the session. The database
> configuration file opens a MySQL connection with a function called `getConnection`, and it also contains
> our **remember me** logic — if there is no session but a remember-token cookie exists, it looks the token
> up in the users table and rebuilds the session.
>
> Now let me show you **authentication**. This is the login handler. We prepare the query
> `SELECT * FROM users WHERE email = ?` — the question mark is a **placeholder**, which means the e-mail can
> never be treated as SQL code. That is how we prevent SQL injection. Then we call `password_verify`, which
> compares the typed password against the hashed password stored in the database. We never store real
> passwords — only bcrypt hashes. On success we save four values in the session: the user id, name, e-mail
> and role, and then we redirect the user to the correct dashboard depending on that role.
>
> This file, `includes/auth.php`, is our **access control**. `isAdmin()` simply checks whether the session
> role equals admin, and `requireAdmin()` redirects to the login page if it does not. Every admin page calls
> `requireAdmin()` on its first line, every customer page calls `requireLogin()`, and every mechanic page
> checks for the mechanic role. It is a simple role-based system, and it works — although being copied into
> each page, it is not as clean as framework middleware would be.
>
> The same file contains two pieces of our **business logic**. `generateBookingId` builds the code a customer
> uses to track a repair: the letters BK, the date, and four random digits. `getQueueNumber` asks the
> database for the highest queue number used today and adds one. So the queue always restarts every day.
>
> This is `process-booking.php`, the file that saved the booking you saw earlier. In order: we generate the
> booking ID, we get today's queue number, we prepare an INSERT into the bookings table with ten
> placeholders, we bind the values, we execute, and if it succeeds we redirect to the tracking page. If it
> fails we show the database error. That is the whole create operation in thirty lines.
>
> The most interesting logic is the **status update**. In our database we have two status columns — one on
> the booking, which is what the customer sees, and one on the repair, which is what the mechanic works
> with. When a mechanic updates a job, both must change together, otherwise the customer sees the wrong
> thing. This is why the code uses a **transaction**: `begin_transaction`, then update the repair, then
> update the booking, then recalculate the total, and finally `commit`. If any step throws an error we call
> `rollback`, so the data can never be left half-updated. That is a real database concept and we use it
> here.
>
> This is also where we found our most interesting **bug**. Two different columns are both named
> `booking_id`: in the bookings table it is the text code like `BK202605041120`, but in the repairs table it
> is a number pointing to the booking's internal id. Our original query selected both, and the text value
> overwrote the number, so the update matched zero rows. The comment in the code explains it, and the fix was
> to alias them — `booking_pk` for the number and `booking_code` for the text. We now update all repair rows
> for a booking, because there is no unique key preventing duplicates.
>
> Let me show one more piece of logic — **inventory**. On the repair-details page, when the admin adds a
> part, the code first checks that the stock is enough, then inserts a row into `repair_parts` and copies the
> current price into a column called `price_at_time`. Then it subtracts the quantity from the parts table. So
> inventory and job cost update together, and because we save the price at the time of use, changing a price
> tomorrow will not change yesterday's invoice. That page also has `updateTotalCost`, which is the single
> function that writes the booking total: parts cost plus labour cost.
>
> Finally, the **database**. We have five tables. **Users** holds all three roles in one table, with a role
> column. **Bookings** holds the customer request, with the public booking code, the queue number and the
> status the customer sees. **Repairs** holds the mechanic's job, with the assigned mechanic, labour cost
> and dates. **Parts** holds the inventory. And **repair_parts** is the join table that records which parts
> were used on which repair, with quantity and the price at that moment. The relationships are enforced with
> foreign keys — for example, if a booking is deleted, its repairs are deleted automatically, and if a
> mechanic account is removed, their repairs are kept but the mechanic id becomes null.
>
> Here in phpMyAdmin you can see the five tables, and here is the booking that was created a few minutes ago
> — the same Booking ID that appeared on the tracking page, now sitting in the database with today's queue
> number. And here is the `repair_parts` table showing the parts we added during the demo. So the full trip
> from form, to server, to database is real and working.
>
> Now —— will put all of this together and tell you what is finished, what is not, and what we do next."

#### Technical terms in simple language

* **mysqli** — PHP's built-in way to talk to MySQL.
* **Prepared statement / placeholder (`?`)** — you send the SQL first and the values separately, so user
  input can never be executed as SQL. This is the defence against SQL injection.
* **Transaction / commit / rollback** — a group of database changes that either all succeed or all fail.
* **Foreign key** — a column that points to a row in another table, with a rule for what happens if that row
  is deleted (`CASCADE` deletes the children, `SET NULL` clears the reference).
* **Join table** — a table that links two other tables; `repair_parts` links repairs to parts.
* **Alias** — giving a column a second name in a query (`r.booking_id AS booking_pk`) so two same-named
  columns do not clash.
* **Autoloader** — a Composer feature that loads PHP classes automatically when they are used.
* **Session** — server-side memory of who is logged in, identified by a cookie.

#### If asked — "Why are you not using Laravel's models and Eloquent?"

> "Because the priority was to get a working system with real features first. The wrapper approach let us do
> that without rewriting the pages. Converting the tables into Eloquent models and moving the queries into
> controllers is exactly the next phase, and we have a clear plan for it."

#### If asked — "Is your system safe from SQL injection?"

> "Mostly yes — every insert and update uses prepared statements with `?` placeholders. But we are honest
> that a few read queries, specifically the search boxes on the admin jobs and customers pages, still build
> the SQL string directly, and that is a fix we have listed in our remaining work."

#### Transition line to Member 5

> "That is the backend and the database. To finish, —— will show you the complete workflow from beginning to
> end, and our honest assessment of the project's progress."

---

### MEMBER 5 — System Integration, Testing, Progress and Conclusion

**Goal:** prove it all works together, then give the honest status and a confident close. This member should
run the full end-to-end workflow and then present the completed / partial / missing lists.

#### Show this (screen plan)

| Point in the script | What should be on screen |
|---|---|
| Integration diagram | A slide: **Browser → Laravel router → legacy PHP → MySQL → back to the browser** |
| End-to-end workflow | Two browser windows side by side (customer and admin), or one window and fast tab switching |
| Testing | The list of manual test cases you performed (a slide) plus the file `tests/Feature/ExampleTest.php` |
| Progress | The status table from Section 2.1 (or a condensed version) |
| Limitations | Three clearly worded bullet points |
| Next steps | A prioritised roadmap slide |
| Closing | The system name slide again |

#### Step-by-step demonstration

1. Show the **integration slide** and give the one-sentence version of how the layers connect.
2. Run the **end-to-end workflow** as a scripted sequence (see below). Prepare the two accounts beforehand in
   separate browser profiles or one normal and one incognito window so you do not have to log in and out
   repeatedly.
3. Switch to the **testing slide** and read out the manual test results honestly.
4. Show `tests/Feature/ExampleTest.php` and say: *"this is Laravel's generated example test — it is
   currently the only automated test we have, and it only checks that the home page answers."*
5. Present the **progress**, **limitations** and **next steps** slides.
6. Close with the conclusion.

#### The end-to-end workflow to demonstrate (10 steps, ~90 seconds)

| Step | Who | Screen / action | What it proves |
|---|---|---|---|
| 1 | Customer (guest or logged in) | Open `/customer/book-service.php`, submit a booking | The form reaches the server and a row is created in `bookings` |
| 2 | — | The page redirects to `/customer/track-status.php?booking_id=...` | The generated Booking ID comes back to the user |
| 3 | — | Note the **queue number** on screen | `getQueueNumber()` read the maximum for today and added one |
| 4 | Admin | Log in → `/admin/jobs.php` → the new booking is at the top of the list | The admin sees the customer's submission immediately |
| 5 | Admin | `/admin/assign-mechanic.php` → pick a mechanic → **Assign** | `repairs.mechanic_id` is set and the booking moves to *in progress* |
| 6 | Mechanic | Log in → `/mechanic/dashboard.php` → the new job appears in the assigned list | The assignment is visible to the mechanic |
| 7 | Mechanic | Click **Work** → set status to **In Progress** → **Update** → add a work note | Both status columns are synchronised inside a transaction |
| 8 | Admin | `/admin/repair-details.php?booking_id=...` → add a part → set labour cost | `repair_parts` gets a row, stock decreases, the booking total is recalculated |
| 9 | Customer | Refresh `/customer/track-status.php` with the Booking ID | The customer now sees the advanced progress bar, the timeline, the parts and the total |
| 10 | Admin | `/admin/dashboard.php` and `/admin/reports.php` | The dashboard KPIs and the revenue report reflect the new data |

#### Say this

> "Thank you, ——. To close our presentation, I will show you how all the parts work together, and then give
> you an honest summary of our progress.
>
> First, the integration. The picture is simple: the **browser** sends a request, the **Laravel router**
> decides which legacy page to load, that page talks to **MySQL** through the database connection, and the
> result comes back as HTML. The database is the single source of truth — the customer portal, the mechanic
> portal and the admin portal all read and write the same five tables. That is why a change made by the
> mechanic instantly appears on the customer's tracking page without anyone sending a message.
>
> Let me prove that with one complete workflow. I will be the customer first. I am submitting a booking for
> a Honda Wave 100 with an engine problem. The system created the booking, gave me the Booking ID and queue
> number, and took me to the tracking page.
>
> Now I switch to the admin window. Here is the same booking at the top of the **Job List** — the admin sees
> it immediately. I will go to **Assign Mechanic** and assign it to our mechanic. In **Repair Details** I
> will record a spare part and set the labour cost. Notice the total updated on screen.
>
> Now the mechanic window. The job is already in the dashboard. I open it, set it to **In Progress**, and add
> a note that I replaced the spark plug. The dashboard counters and the doughnut chart update as well.
>
> And now back to the customer's tracking page. Same Booking ID — and the progress bar has moved, the
> timeline is further along, and the parts and cost are shown. That is the whole system working together,
> using real data in a real database.
>
> Now, our **testing**. We tested manually, following test cases like these: registration with valid,
> duplicate and mismatched passwords; login with correct, wrong and non-existent credentials; booking with
> and without being logged in; searching a valid and an invalid Booking ID; assigning a mechanic; updating
> status in both directions; adding a part with enough stock and with insufficient stock; deleting a
> mechanic who still has jobs, which the system correctly blocks; and viewing every report. Most of these
> passed, and the failures are exactly the ones we have already told you about. On the automated side, I
> have to be honest: this is the only test file we have — it is Laravel's generated example test, and it
> only checks that the home page returns successfully. We plan to add proper feature tests for booking and
> status updates.
>
> So, our **completed features**: the entire customer portal, the entire mechanic portal, and all eleven
> admin pages — including inventory management, mechanic management, customer management, repair costing
> with automatic stock deduction, and the reports module with three charts and six report views. The database
> is complete with five tables, foreign keys and sample data. Authentication with three roles, remember-me
> and password reset all work.
>
> Our **partially finished features**: the booking-confirmation e-mail — the code exists, but it is not
> called anywhere yet, so customers do not actually receive it. The customer avatar dropdown — the
> JavaScript exists, but the menu markup is missing. And the invoice page, which is broken by two small
> path problems.
>
> Our **not yet started features**: the migration of the code into Laravel's structure, and an automated
> test suite. Our database also does not yet have a notifications table, even though our earlier README
> mentioned one — we are correcting that documentation.
>
> Our **next steps**, in priority order. **First**, fix the four broken items: the invoice page path, the
> cancel-booking endpoint, the booking-confirmation e-mail call, and the avatar menu. **Second**, add
> server-side validation to the booking form, and replace the remaining string-built SQL with prepared
> statements, because that is our main security gap. **Third**, start the Laravel migration — create models,
> migrations and controllers for bookings, repairs and parts, and move the queries into them. **Fourth**,
> write automated tests for the booking and status-update flows. And **fifth**, polish — standardise the
> currency, and add print and export options to the reports.
>
> To conclude: MotoRepair Pro is a **working motorcycle repair shop management system**. A customer can book
> a repair online and track it; a mechanic can manage their assigned jobs; an admin can manage the whole
> shop, including inventory, staff, costing and reports — all on one shared database. It is not finished —
> and we have told you exactly where the gaps are — but the core of the system is real, working and tested.
> Thank you very much, and we are happy to take your questions."

#### Technical terms in simple language

* **Integration** — the parts of the system working together (frontend, backend, database).
* **End-to-end test** — following a complete workflow from the user's first click to the final database row.
* **Manual testing** — a person testing by following written steps.
* **Automated test** — code that tests other code.
* **Regression** — accidentally breaking something that used to work; this is what automated tests catch.
* **Roadmap** — a prioritised plan of what to build next.

#### If asked — "If you had two more weeks, what would you do first?"

> "Fix the four broken items, then add server-side validation and replace the string-built SQL with prepared
> statements, then begin the Laravel migration. Those three things would make the biggest difference to both
> quality and security."

#### Closing line (returning to Member 1 if the whole group stands together)

> "That concludes our presentation of MotoRepair Pro. Thank you for listening — we are ready for your
> questions."

---
---

## 7. Live Demonstration and Recording Guide

This section is the "stage direction" for the recording. It does **not** repeat what each member says — that is already written in Section 6. It only answers four practical questions:

1. **Who speaks, and in what order?**
2. **What must be visible on the screen at that exact moment?**
3. **Which code file, folder or database view is worth opening, and why?**
4. **How does one member hand over to the next without awkward silence?**

### 7.1 Recording order and time budget

| Order | Member | Presentation role | Main screens used | Target time |
|:---:|:---|:---|:---|:---:|
| 1 | **Member 1** | System Introduction and Overview | Home / landing page, the three-portal map, the finished-features list | **3:00** |
| 2 | **Member 2** | Frontend Design and Visual Components | Home page, booking form, customer dashboard, admin dashboard, reports | **4:00** |
| 3 | **Member 3** | Frontend Functionality and User Interaction | Register, login, booking form, track-status, mechanic job list | **4:00** |
| 4 | **Member 4** | Backend Code and Database | VS Code (`routes/web.php`, `login.php`, `process-booking.php`, `jobs.php`), phpMyAdmin, the `.sql` file | **4:30** |
| 5 | **Member 5** | System Integration, Testing, Progress and Conclusion | Admin assign-mechanic → mechanic work-on-repair → admin repair-details → reports | **3:30** |

**Total content time ≈ 19 minutes.** Allow 2–3 extra minutes for the opening title card and the question buffer, so plan the recording session for roughly **22 minutes**.

> **Balance check.** Member 4 has the longest slot because the backend walkthrough covers routes, three code files and a database demo. Member 1 has the shortest because they set the context and do not open any code. If your group wants an even split, move the "reports" screen from Member 2 to Member 5.

### 7.2 Pre-recording checklist (do this before you press Record)

Run this list together, five minutes before recording. Almost every "bad take" in a student recording is caused by one of these items.

- [ ] **Start XAMPP.** Both **Apache** and **MySQL** must show green in the XAMPP Control Panel.
- [ ] **Import the database** once, so the data is fresh: open `http://localhost/phpmyadmin`, create/select the `motorcycle_repair_shop` database, then import `legacy/motorcycle_repair_shop.sql`.
- [ ] **Confirm the app loads.** Open `http://localhost/motorcycle_repair_shop_laravel/public/` (the Laravel `public` folder). The home page should appear.
- [ ] **Prepare three logins** in separate browser tabs or profiles:
  - Admin — `admin@motorepair.com` / `password`
  - Mechanic — `mechanic@motorepair.com` / `password`
  - Customer — register a fresh account during the demo, or keep one pre-created.
  > The seeded admin and mechanic passwords are both literally `password`. Do **not** repeat the passwords written in the old README (they are wrong), and do **not** use the accounts `jane@example.com` or `mike@gmail.com` — their stored password values are invalid, so they cannot log in.
- [ ] **Browser hygiene.** Use a clean browser window. Close unrelated tabs, mute notifications, set zoom to about **110 %** so text is readable in the video, and switch off any auto-fill pop-ups.
- [ ] **Two windows side by side.** Keep the **browser** on one side and **VS Code** on the other, so Member 4 can switch instantly without searching.
- [ ] **Do NOT open `legacy/send-notification.php`.** It contains hard-coded e-mail (Gmail) credentials. Keep it closed while recording; if it appears in the code editor's file list, do not click it.
- [ ] **Move the mouse slowly and deliberately.** Point at one thing at a time. Pause for a second after each click so the viewer sees the result.

### 7.3 What should be on screen for each member

**Member 1 — System Introduction and Overview (3:00)**

| Segment | On screen | Point at / do | Time |
|:---|:---|:---|:---:|
| Opening titles | Title slide: system name, group members, subject | Read the title, then minimise the slide | 0:20 |
| The problem | One slide (or the home page hero) listing the four problems the system solves | Speak to the four problems in plain words | 0:50 |
| The landing page | Live home page of the app | Scroll slowly top to bottom once | 0:40 |
| The three portals | A simple diagram: Customer / Mechanic / Admin | Explain who each portal is for | 0:40 |
| Finished vs remaining | A short checklist slide | Say what is done and what is not | 0:30 |

**Member 2 — Frontend Design and Visual Components (4:00)**

| Segment | On screen | Point at / do | Time |
|:---|:---|:---|:---:|
| Visual identity | Home page | Point at the colour scheme, the font, the logo/spacing | 0:50 |
| The booking form | Live booking page | Scroll through the fields, the dropdowns, the date picker | 0:50 |
| The customer portal | Customer dashboard | Point at the sidebar, the cards, the status badges | 0:50 |
| The admin portal | Admin dashboard | Point at the stat cards, the sidebar, the tables | 0:50 |
| Data presentation | Reports page | Point at the three charts and the six report views | 0:40 |

**Member 3 — Frontend Functionality and User Interaction (4:00)**

| Segment | On screen | Point at / do | Time |
|:---|:---|:---|:---:|
| Registration | Register page | Fill and submit a new account | 0:50 |
| Login | Login page | Log in as the new customer | 0:40 |
| Booking | Booking form | Submit a real booking; show the success/redirect | 1:00 |
| Tracking | Track-status page | Enter the Booking ID; show the status timeline | 0:50 |
| Mechanic view | Mechanic job list | Show the assigned job, open it | 0:40 |

**Member 4 — Backend Code and Database (4:30)**

| Segment | On screen | Point at / do | Time |
|:---|:---|:---|:---:|
| Project structure | VS Code file explorer | Expand `legacy/`, `routes/`, `app/` | 0:40 |
| The router | `routes/web.php` | Show `runLegacy()` and the route list | 0:50 |
| Authentication | `legacy/login.php` + `legacy/includes/auth.php` | Show the prepared statement and `password_verify()` | 0:50 |
| Writing a booking | `legacy/process-booking.php` | Show the INSERT and the redirect | 0:40 |
| The database | phpMyAdmin, `bookings` table | Show the row that the previous demo created | 0:50 |
| Schema file | `legacy/motorcycle_repair_shop.sql` | Scroll the five `CREATE TABLE` statements | 0:40 |

**Member 5 — System Integration, Testing, Progress and Conclusion (3:30)**

| Segment | On screen | Point at / do | Time |
|:---|:---|:---|:---:|
| End-to-end flow | Admin → Assign Mechanic | Assign the new booking to a mechanic | 0:50 |
| Mechanic action | Mechanic → Work-on-Repair | Update the status and add a part | 0:50 |
| Admin costing | Admin → Repair-Details | Show the cost total and stock deduction | 0:40 |
| Reporting | Admin → Reports | Show the charts refresh with the new data | 0:40 |
| Closing | Progress slide (done / partial / remaining) | Deliver the honest summary and the roadmap | 0:30 |

### 7.4 When to show what (live system vs screenshots vs folder tree vs code vs database)

| Tool / view | Show it when… | Members who use it |
|:---|:---|:---|
| **Live running system** | You are demonstrating a feature the viewer must *see working* (booking, tracking, status change). | 1, 2, 3, 5 |
| **Slides** | You are explaining context, the problem, the timeline or the roadmap — anything with no screen equivalent. | 1, 5 |
| **Folder tree / file explorer** | You need to prove the project's size and structure — one quick expansion is enough. | 4 |
| **Code editor (VS Code)** | You are explaining a *specific* small function. Keep each file open for under a minute. | 4 |
| **phpMyAdmin** | You want to prove that data really is stored — show the row the live demo created. | 4, 5 |
| **The `.sql` schema file** | You want to show the structure of all five tables at once. | 4 |

> **Rule of thumb:** show the **live system** for about 60 % of the recording, **slides** for about 25 %, and **code or database** for about 15 %. Do not read long blocks of source code aloud.

### 7.5 Which code snippets are worth presenting (and why)

Only **five** snippets are worth opening on screen. Everything else should be described, not scrolled through.

| # | File | Approx. lines | What it proves | How to show it | Member |
|:---:|:---|:---:|:---|:---|:---:|
| 1 | `routes/web.php` | 5–18 | How every web address is answered by `runLegacy()` | Open the file, highlight `runLegacy()` only | 4 |
| 2 | `legacy/login.php` | 31–38 | Login uses a **prepared statement** and `password_verify()` | Highlight the `prepare/bind/execute` block | 4 |
| 3 | `legacy/includes/auth.php` | 6–41 | The three roles and the `require...()` guards | Show `isAdmin()`, `requireAdmin()` | 4 |
| 4 | `legacy/process-booking.php` | 19–27 | A booking is inserted with a prepared statement, then the user is redirected | Highlight the `INSERT` + `header('Location'...)` | 4 |
| 5 | `legacy/admin/jobs.php` | 16–52 | An admin query that joins bookings and reads status | Show one `SELECT ... JOIN` | 4 |

**What each snippet demonstrates, in one sentence (for the speaker):**

1. **`runLegacy()`** — "This one function is the bridge: it finds the requested PHP file inside `legacy/` and runs it, so the old application keeps working inside the new Laravel project."
2. **The login query** — "We never paste user input straight into SQL. We use a **prepared statement**, which separates the command from the data and blocks SQL-injection."
3. **The auth guards** — "Before an admin page runs, `requireAdmin()` checks the session role and sends anyone who is not an admin back to the login page."
4. **The booking INSERT** — "This takes the form data and writes one new row into the `bookings` table, then sends the customer to their tracking page."
5. **The jobs JOIN** — "This query pulls together data from two tables — the booking and its repair job — so the admin sees everything in one list."

### 7.6 Screen transitions and hand-offs

The hand-off is where recordings usually look unprofessional (dead air, two people talking). Use these exact transitions.

- **Member 1 → Member 2.** Member 1 ends on the "finished vs remaining" checklist, then says: *"Now that you know what the system is, let me hand over to [Member 2], who will show you how it actually looks."* Member 2 is already on the home page — no click needed.
- **Member 2 → Member 3.** Member 2 ends on the Reports page, then says: *"That is the design. But a good-looking screen is useless if the buttons do not work — so here is [Member 3] to show you the working parts."* Member 3 clicks **Register** and starts.
- **Member 3 → Member 4.** Member 3 ends inside the mechanic job list, then says: *"Everything you just saw was the front end. [Member 4] will now show you what happens on the server when you press those buttons."* Member 4 alt-tabs from the browser to VS Code.
- **Member 4 → Member 5.** Member 4 ends on the `bookings` table in phpMyAdmin showing the row created earlier, then says: *"That is how the data is stored. To finish, [Member 5] will put the whole thing together and tell you where we are."* Member 5 switches back to the browser.
- **Member 5 → end.** Member 5 ends on the progress slide and the closing line. Hold the final slide (system name, thank-you) on screen for three seconds before stopping the recording.

### 7.7 Backup plan if something fails live

- **Take screenshots of every key screen in advance.** Place them in one folder. If Apache or MySQL stops working, switch to screenshots and describe the same steps — the script still makes sense without the live system.
- **If the database will not connect**, open phpMyAdmin and show the tables instead of the live app; the explanation in Section 5 still applies.
- **If a page shows a PHP error** (for example the invoice page — see Section 8.3), do not hide it. Say: *"This is one of the known issues we will list later — the page has a path problem we are still fixing."* Honesty about a known bug scores better than pretending.
- **Rehearse the demo clicks once** without recording. Know exactly which tab is the admin, which is the mechanic, and which is the customer.

### 7.8 Narration style tips

- Speak at a normal, slightly slower pace. Pause for **one full second** after each click so the result is visible.
- Point with the **cursor**, not your finger; move it in straight lines.
- Use "**we**", not "I" — it is a group project.
- Never apologise for the project being unfinished; state it as *"not yet implemented"* and give the next step.
- Each member should memorise only their **opening sentence** and their **transition sentence**. Everything in between can be rephrased naturally.

---

## 8. Completed Features, Limitations, and Remaining Tasks

This section is the honest, overall status of the project. It is deliberately direct: the panel is more impressed by a team that knows exactly what works and what does not than by a team that claims everything is finished.

### 8.1 Completed features (working and demonstrated live)

These features were confirmed working against the actual source code and can be shown without risk.

| # | Feature | Where it lives | Status |
|:---:|:---|:---|:---:|
| 1 | Public landing page with service information and a booking call-to-action | `legacy/index.php` | ✅ Working |
| 2 | Customer registration with password hashing (`password_hash`) | `legacy/register.php` | ✅ Working |
| 3 | Login for all three roles (customer / mechanic / admin) using prepared statements + `password_verify` | `legacy/login.php` | ✅ Working |
| 4 | Remember-me cookie handling | `legacy/login.php` + `legacy/includes/auth.php` | ✅ Working |
| 5 | Password reset using a generated token with a one-hour expiry | `legacy/login.php` (lines 118–152), `legacy/reset-password.php` | ✅ Working |
| 6 | Public booking form (works for guests and logged-in users) | booking page → `legacy/process-booking.php` | ✅ Working |
| 7 | Automatic Booking ID generation (`BK` + date + random) and daily queue number | `legacy/includes/auth.php` (43–56) | ✅ Working |
| 8 | Customer booking tracking by Booking ID (status timeline) | `legacy/customer/track-status.php` | ✅ Working |
| 9 | Customer dashboard and booking status page | `legacy/customer/dashboard.php`, `booking-status.php` | ✅ Working |
| 10 | Mechanic dashboard and assigned-job list | `legacy/mechanic/dashboard.php`, `my-repairs.php` | ✅ Working |
| 11 | Mechanic "work on repair" — update repair status and progress | `legacy/mechanic/work-on-repair.php` | ✅ Working |
| 12 | Admin dashboard with summary statistics | `legacy/admin/dashboard.php` | ✅ Working |
| 13 | Admin booking/job management and status updates | `legacy/admin/jobs.php` | ✅ Working |
| 14 | Admin assign-mechanic workflow | `legacy/admin/assign-mechanic.php` | ✅ Working |
| 15 | Admin repair details with labour cost, parts, and automatic stock deduction | `legacy/admin/repair-details.php` | ✅ Working |
| 16 | Spare-part inventory management (add / edit / delete parts, stock counts) | `legacy/admin/inventory.php` | ✅ Working |
| 17 | Mechanic management (add / edit / delete mechanics, with a guard that blocks deleting staff who still have jobs) | `legacy/admin/mechanics.php` | ✅ Working |
| 18 | Customer management and customer details view | `legacy/admin/customers.php`, `customer-details.php` | ✅ Working |
| 19 | Mechanic performance page | `legacy/admin/mechanic-performance.php` | ✅ Working |
| 20 | Reports module — three charts (Chart.js) and six report views | `legacy/admin/reports.php` | ✅ Working |
| 21 | Logout (session destruction) | `legacy/logout.php` | ✅ Working |
| 22 | Complete MySQL schema — five tables with foreign keys and sample data | `legacy/motorcycle_repair_shop.sql` | ✅ Working |
| 23 | Laravel route wrapper that serves every legacy page through clean URLs | `routes/web.php` | ✅ Working |

### 8.2 Partially implemented features

| # | Feature | What exists | What is missing |
|:---:|:---|:---|:---|
| 1 | **Booking-confirmation e-mail** | `sendBookingConfirmation()` exists and PHPMailer is bundled in `legacy/phpmailer/` | The function is **never called**, so customers never actually receive the e-mail. |
| 2 | **Invoice page** | `legacy/admin/generate-invoice.php` prints a full invoice layout | Two path bugs stop the page from loading, and every link to it points to the wrong folder (see 8.3). |
| 3 | **Customer avatar dropdown** | The `toggleDropdown()` JavaScript is present | There is **no `#userDropdown` element** in the markup, so clicking the avatar does nothing. |
| 4 | **Laravel layer** | Laravel 12 is installed; routes, bootstrap and config all respond correctly | No Eloquent models, migrations or controllers for the business logic — the framework is only a wrapper. |
| 5 | **Notification system** | Referenced in the older README | A `notifications` table does **not** exist in the database or the schema file. The documentation overstates this feature. |

### 8.3 Known broken items (reported honestly)

These are defects confirmed in the source. If any of them appears on screen during the demo, name it as a known issue instead of clicking past it.

**1. The invoice page throws a fatal error — wrong `require` path.**

- File: `legacy/admin/generate-invoice.php`, line 3.
- It says `require_once 'config/database.php';` — a **relative** path.
- Every other admin page correctly uses `require_once __DIR__ . '/../config/database.php';`.
- Because the Laravel wrapper changes the working directory to the file's own folder (`legacy/admin/`), PHP looks for `legacy/admin/config/database.php`, which does not exist → fatal error.
- **One-line fix:** change line 3 to the same `__DIR__ . '/../config/database.php'` form the other pages already use.

**2. Every link to the invoice points to a non-existent file.**

- `legacy/admin/repair-details.php` line 424 uses `../generate-invoice.php`.
- `legacy/customer/booking-status.php` line 282 and `legacy/customer/track-status.php` line 135 also use `../generate-invoice.php`.
- From these pages, `../generate-invoice.php` resolves to the project root, but the real file lives in `admin/`. So the links 404 even once bug #1 is fixed.
- **Fix:** point the links at `admin/generate-invoice.php` (or the equivalent Laravel route).

**3. Cancelling a booking leads to a 404 — the endpoint is missing.**

- `legacy/customer/booking-status.php` line 344 calls `window.location.href = '../cancel-booking.php?id=' + id;`
- The file `legacy/cancel-booking.php` **does not exist**. The "Cancel" button therefore fails.
- **Fix:** either create the cancel endpoint or disable the button until the feature is built.

**4. Three mechanic pages redirect to a customer login that does not exist.**

- `legacy/mechanic/dashboard.php`, `legacy/mechanic/my-repairs.php` and `legacy/mechanic/work-on-repair.php`, each on **line 8**, use `header('Location: ../customer/login.php');`
- The login page is at `legacy/login.php`, not `legacy/customer/login.php`. The redirect therefore fails for anyone not logged in.
- **Fix:** change line 8 in all three files to `../login.php`.

**5. Documentation inconsistencies in the old README.**

- The README claims the admin/mechanic passwords are `admin123` and `mechanic123`; the seeded hashes actually match the password `password`.
- The README mentions a `notifications` table that does not exist.
- **Fix:** correct the README so new developers are not misled.

**6. Two unusable seeded accounts.**

- `jane@example.com` has an invalid password hash and `mike@gmail.com` stores a plain-text `123`. Neither can log in. Do not use these accounts in the demo.

### 8.4 Security and code-quality limitations

| Area | Observation | Risk |
|:---|:---|:---|
| SQL building | Most queries use **prepared statements** (good), but a few admin/report queries build SQL by string concatenation. | SQL-injection risk if input reaches those queries. |
| Input validation | Login and booking validate required fields, but some admin forms rely on client-side checks only. | Malformed data could reach the database. |
| Hard-coded credentials | `legacy/send-notification.php` contains hard-coded e-mail credentials. | Must be moved to configuration and rotated. Do not display on screen. |
| Automated tests | Only the two generated Laravel example tests exist (`tests/Feature/ExampleTest.php`, `tests/Unit/ExampleTest.php`); nothing tests the real business logic. | No safety net against regressions. |
| Dead code | `public/assets/js/main.js` is not used by any page. | Minor — confusing for future developers. |
| Duplicate column meaning | `work-on-repair.php` (lines 17–20) reads `booking_id` in a way that mixes the booking's primary key with its booking code. | Subtle data-matching bug worth investigating. |

### 8.5 Remaining tasks (prioritised roadmap)

| Priority | Task | Why it matters | Effort |
|:---:|:---|:---|:---:|
| **1** | Fix the four broken items in 8.3 (invoice path, invoice links, cancel-booking, mechanic redirects) | They are small and visible; fixing them removes every 404 and error page from the demo. | Low |
| **2** | Call `sendBookingConfirmation()` after a successful booking | The e-mail feature already exists but is dormant. | Low |
| **3** | Add server-side validation to the booking and admin forms | Closes the main data-quality gap. | Medium |
| **4** | Replace the remaining string-built SQL with prepared statements | Closes the main security gap. | Medium |
| **5** | Move the hard-coded mail credentials into a config file and rotate them | Security hygiene. | Low |
| **6** | Begin the Laravel migration — models, migrations and controllers for bookings, repairs and parts | Moves the project from "wrapper" to a real framework structure. | High |
| **7** | Write automated tests for booking creation and status updates | Adds a regression safety net. | Medium |
| **8** | Standardise currency formatting and add print/export to reports | Polish and usability. | Low |

### 8.6 Overall progress estimate

| Layer | Completion (estimated) | Basis |
|:---|:---:|:---|
| Customer portal (frontend + backend) | **~95 %** | All pages work except booking cancellation. |
| Mechanic portal | **~95 %** | Works, except the login-redirect path bug. |
| Admin portal | **~90 %** | All pages work; the invoice page is broken. |
| Database | **~100 %** | Five tables, keys and sample data are complete. |
| Authentication & authorization | **~95 %** | Three roles, remember-me and reset all work. |
| Laravel migration | **~10 %** | Routing wrapper only. |
| Automated testing | **~5 %** | Example tests only. |
| **Overall working-system estimate** | **≈ 80 %** | A genuinely usable system with a known, documented list of gaps. |

> Present the number like this: *"If you count the working features, we are around eighty percent — but we would rather say we have a complete, working core and a short, honest list of things left to do."*

---

## 9. Final Conclusion

### 9.1 One-paragraph summary of the project

**MotoRepair Pro** is a working, database-driven motorcycle repair shop management system built as a Laravel 12 project whose active application code lives in PHP 8.2 with MySQL and MySQLi. It serves three kinds of users from one shared database: **customers** who book a repair online and track its progress by Booking ID, **mechanics** who manage the jobs assigned to them, and **administrators** who control bookings, staff, spare-part inventory, repair costing and reporting. The customer, mechanic and admin portals are all implemented and demonstrate real create / read / update / delete operations against five related tables. What remains is not the core of the system but its refinement: four small path-related defects, a dormant confirmation e-mail, an unfinished migration of the legacy code into Laravel's own structure, and an automated test suite. In short — **the system works; the work that is left is about quality, security and structure, and the team knows exactly which items those are.**

### 9.2 What the team can honestly claim

- We built a **complete three-role web application** with real authentication and authorization.
- We designed and shipped a **shared five-table relational database** with foreign keys and sample data.
- We implemented a **full customer journey** — book → receive a Booking ID → track the repair by status.
- We implemented a **mechanic workspace** and an **eleven-page admin back office** including inventory, staff management, repair costing with automatic stock deduction, and a reports module with charts.
- We built a **Laravel wrapper** that gives the legacy PHP application clean, framework-managed URLs.
- We **tested the system manually** across a documented set of test cases and found real defects, which we have reported rather than hidden.

### 9.3 What the team should say they would do next

The single most valuable sentence a student can say at the end of a progress presentation is a **specific, prioritised plan**. Ours is:

1. **Fix** the four broken items (invoice path, invoice links, cancel-booking endpoint, mechanic login redirects).
2. **Activate** the booking-confirmation e-mail that already exists in the code.
3. **Harden** the system — server-side validation and prepared statements everywhere.
4. **Migrate** the business logic into Laravel models, migrations and controllers.
5. **Test** — automated tests for booking and status updates.
6. **Polish** — currency consistency, and print/export in reports.

### 9.4 Final takeaway for the panel

> "MotoRepair Pro turns a manual, paper-based repair-shop process into one connected system. A customer books online; a mechanic works the job; an admin manages the shop, the parts and the numbers — all sharing one database. We are not finished, and we have been honest about exactly where we are. But the core of our system is real, working and demonstrated today. Thank you."

### 9.5 Quick-reference card (print this and keep it beside the camera)

| Question the panel may ask | Short, safe answer |
|:---|:---|
| What technology did you use? | PHP 8.2 with MySQLi for the application, inside a Laravel 12 project that routes the pages; MySQL for the database. |
| Why Laravel if the code is plain PHP? | We adopted Laravel as the project shell and are migrating the code into it; the routing wrapper is already in place, the models and controllers are the next step. |
| Is it secure? | Authentication uses hashed passwords and prepared statements. We know our remaining gap is a few string-built queries, and that is task 4 on our roadmap. |
| Does it really save data? | Yes — we can show the row in phpMyAdmin that the live demo just created. |
| What is not finished? | Four small path bugs, the booking-confirmation e-mail, the Laravel migration, and automated tests. All are listed in our documentation. |
| Did you do any testing? | Yes — documented manual testing across registration, login, booking, tracking, status updates, inventory limits and reports. |

**End of document.**
