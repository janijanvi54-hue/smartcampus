# DSVV SmartCampus – Campus Resource & Facility Management System

A full-stack web application for **Dev Sanskriti Vishwavidyalaya (DSVV), Shantikunj,
Haridwar** that lets students and faculty discover, book, and manage campus resources —
academic rooms (classrooms, computer labs, seminar halls, library study rooms, meeting
rooms) and campus facilities (auditoriums, hostels, cafeterias, health centres, guest
house, amenities) — while giving administrators live utilization analytics, smart resource
recommendations, booking approval, complaints handling, and announcements.

Built with **PHP 8 + MySQL (XAMPP)** — no frameworks, no build step, plain PHP with a
clean MVC-flavoured structure, Bootstrap 5 UI and Chart.js dashboards.

---

## Features

### Role-based access
| Role     | Access |
|----------|--------|
| Student  | Browse resources, live availability, book student-eligible resources, my bookings, notifications, report problems |
| Faculty  | Book resource wizard (auto-fill + recommendation engine), my bookings, notifications |
| Admin    | Full dashboard, resource CRUD, booking approval/rejection, user management, complaints, announcements, analytics & reports (CSV export) |

- Login, registration (admin accounts can only be created by an existing admin), password recovery (verify email + ID, then reset).
- CSRF protection on all forms, password hashing (`password_hash`), PDO prepared statements everywhere.

### Role-scoped booking
- Every resource has a **bookable-by** setting (`all`, `student`, `faculty`, or `admin`).
  Defaults by type: classrooms/seminar halls/meeting rooms/auditoriums → faculty; computer
  labs/library/study rooms → all; hostels/canteens/health centres/guest houses/amenities → admin.
- Logged-in students/faculty only **see** resources they are allowed to book, and the
  server enforces it with a 403 on any direct booking/recommend-API attempt.
- Anonymous users must log in to book — the **"Login to book"** button carries a `return`
  parameter so the user lands back on that resource's booking page after login. The same
  applies when a not-logged-in user tries to open any protected student/faculty/admin page:
  the login redirect keeps the destination, so login drops the user right back where they
  were heading.

### Smart Resource Recommendation Engine
When a requested resource is booked out or unsuitable, the system recommends ranked
alternatives using a weighted scoring model:

1. **Capacity headroom** (largest weight) — best-fit capacity for the expected headcount
2. **Current utilization** — prefer resources that are less crowded right now
3. **Expected utilization vs 80% ideal** — the resulting load should sit near the sweet spot
4. **Location preference bonus** — resources matching a preferred block/location get a boost

Each recommendation returns a **score** and **human-readable reasons**
(e.g. *"Sufficient capacity (50) for 45 users"*, *"Currently 38% occupied"*).
With one click a user can **accept a recommendation** — the original booking is cancelled
and the recommended resource is booked instead (with admin approval if required).

### Utilization insights (per spec)
- Utilization = `(users_count / capacity) × 100`, computed from usage history.
- Status bands: **under-utilized ≤ 30%** · **normal ≤ 70%** · **high ≤ 100%** · **overcrowded > 100%**
- Admin dashboard shows type-wise, daily, hourly, per-resource, and status analytics plus
  auto-generated **smart insights** (e.g. *"Auditorium 6 is running at 28% — consider
  relocating classes here."*, *"Commercial Computer Lab is overcrowded at 105%."*).
- CSV export for resources, bookings, overcrowded resources, under-utilized resources, complaints, and users.

### Booking rules
- Validations enforced on the **server**: past dates rejected, `end > start`, required
  fields, capacity check, and **time-slot conflict detection** (no double-booking).
- Approvals: admin can approve/reject; double-approval conflict re-check before approving.
- Auto-approve toggle available in `settings` (default off → all bookings require admin approval).

### Other
- Live availability widget (per resource, with hourly slot grid).
- Notifications for booking status changes, complaint updates, and announcements.
- Announcements (admin) with mark-as-read notifications for all users.
- Complaint management with priority and status workflow (reported → in progress → resolved).

---

## Seed data (DSVV campus)

`database/generate_sql.php` seeds the real DSVV infrastructure directory —
**55 resources across 12 types**, plus 8 users, 23 bookings, 7532 usage records,
5 complaints, and 4 announcements:

| Type           | Count |
|----------------|-------|
| Hostels        | 12 (Boys: Shakuntala, Shaunak, Sandipani, Vasishtha; Girls: Panini, Sanghmitra, Utkarsha, Annapurna; Working Women: Vatsalya; Faculty: Acharya Kutir, Vasundhara, Jyotirgamaya) |
| Amenities      | 10 (Gau Sadan, Kaustubh Hall, Brahma Vakya, Goshala, Payvanya, Yagyashala, Panchagavya, Gaushala, Purak, Sanjeevani) |
| Health Centres | 8 (Shri Badri Vishal Ayurvedic Hospital, Panchakarma Centre, Yagyopathy, Naturopathy, Homoeo, Yoga Therapy, Rishikul, Sanjeevani) |
| Auditoriums    | 6 (Auditorium 1–6) |
| Computer Labs  | 4 (Commercial, Academic, Digital Library, Faculty) |
| Classrooms     | 3 |
| Seminar Halls  | 3 |
| Cafeterias     | 3 |
| Meeting Rooms  | 2 |
| Study Rooms    | 2 |
| Library        | 1 |
| Guest House    | 1 |

Utilization is seeded so the analytics tell a real story, e.g. **Commercial Computer Lab
~105%** (overcrowded — recommend alternatives), **Computer Lab 3 ~38–41%** (moderate —
top recommendation for large batches), **Auditorium 6 ~28%** and **Shaunak Bhawan ~30%**
(under-utilized — suggest relocating classes there).

---

## Tech Stack
- **Backend:** PHP 8.x (plain, PDO + MySQL)
- **Database:** MySQL / MariaDB (XAMPP), DB name `smartcampus`
- **Frontend:** Bootstrap 5, Bootstrap Icons, Chart.js, vanilla JS
- **No build step** — just drop the folder into `htdocs` (or run PHP's built-in server).

---

## Setup

### 1. Prerequisites
- XAMPP (or any PHP 8 + MySQL environment) — start **Apache** and **MySQL** from the XAMPP Control Panel.

### 2. Create & import the database
Open a terminal in the `database/` folder:

```bash
# create the database
mysql -u root -e "CREATE DATABASE IF NOT EXISTS smartcampus CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"

# import schema + seed data
mysql -u root smartcampus < smartcampus.sql
```

> PowerShell note: the `<` redirect is not supported; use
> `Get-Content smartcampus.sql -Raw | mysql -u root smartcampus`.

`database/smartcampus.sql` is generated by `database/generate_sql.php` (run
`php database/generate_sql.php` to regenerate — it seeds realistic usage history for the
last 60 days so the analytics look alive).

### 3. Point the app at your database
The app reads credentials from environment variables first, then falls back to local
defaults (`root`, empty password, database `smartcampus`):

| Env var    | Purpose                        | Local default |
|------------|--------------------------------|---------------|
| `DB_HOST`  | MySQL host                     | `localhost`   |
| `DB_PORT`  | MySQL port                     | `3306`        |
| `DB_NAME`  | Database name                  | `smartcampus` |
| `DB_USER`  | Database user                  | `root`        |
| `DB_PASS`  | Database password              | *(empty)*     |
| `APP_URL`  | Public site URL (optional)     | auto-detected |

On the first request against an **empty** database the app imports `database/smartcampus.sql`
automatically (it strips the `CREATE DATABASE`/`USE` header lines), so a fresh cloud DB
needs no manual import.

### 4. Serve the app
Option A — XAMPP: copy the `smartcampus` folder into `C:\xampp\htdocs\` and open
`http://localhost/smartcampus/`.

Option B — PHP built-in server (no Apache needed):
```bash
php -S 127.0.0.1:8090 -t smartcampus
# open http://127.0.0.1:8090/
```
The app auto-detects its base URL, so it works from any subfolder.

---

## Deploy to Render

> As of 2026 Render **no longer provides a native PHP runtime**, so this repo ships a
> `Dockerfile` (PHP 8.2 + Apache + `pdo_mysql`) and a `render.yaml` Blueprint.

### 1. Put the code on GitHub
Before every push, run the quick sanity check (PHP `lint` + JS bracket balance) from the
project folder:
```powershell
powershell -ExecutionPolicy Bypass -File check.ps1
```
Only push when it prints "All checks passed". Then create a GitHub repo, upload the
`smartcampus` folder (including `Dockerfile`, `render.yaml` and `check.ps1`), and push.

### 2. Create a MySQL database
- **Free option (recommended for the hackathon):** a free MySQL host such as
  `freesqldatabase.com` or `db4free.net`. Note the host, port, DB name, user and password.
- **Paid option:** deploy Render's MySQL template (`render-examples/mysql`) as a private
  service with a disk (not available on the free tier).

### 3. Create the Web Service on Render
1. New → **Web Service** → select your GitHub repo → language **Docker** (Render finds the `Dockerfile`).
2. Set these **environment variables**:

   | Key        | Value                                   |
   |------------|-----------------------------------------|
   | `DB_HOST`  | your MySQL host                          |
   | `DB_PORT`  | `3306`                                   |
   | `DB_NAME`  | your database name                       |
   | `DB_USER`  | your MySQL user                          |
   | `DB_PASS`  | your MySQL password                      |
   | `APP_URL`  | `https://smartcampus.onrender.com` (your site URL) |

3. Deploy. On first boot the app **auto-installs the schema and seed data** into the empty
   database, then serves the site.

### Render notes
- The free web-service tier **spins down after ~15 minutes idle** and takes 30–60s to wake.
  Open the site once before your demo and keep the tab alive.
- `config/`, `database/` and `includes/` are blocked from direct web access via
  `.htaccess`; the database password lives only in the `DB_PASS` environment variable.
- Demo accounts still use password `Password123!` (see below).
All seeded accounts use password: `Password123!`

| Role    | Email                     |
|---------|---------------------------|
| Admin   | admin@smartcampus.local   |
| Faculty | faculty@smartcampus.local |
| Student | student@smartcampus.local |

The login page includes one-click **demo login** buttons for these three accounts.

---

## Project structure
```
smartcampus/
├── index.php                 Home page
├── login.php / register.php / logout.php / forgot-password.php / about.php
├── config/
│   ├── config.php            APP_URL auto-detection, constants, thresholds
│   ├── database.php          PDO singleton
│   └── session.php           Sessions + CSRF helpers
├── includes/
│   ├── functions.php         Utilization, availability, validation, recommendation engine, analytics, mailers
│   ├── auth.php              current_user / require_role / redirects
│   ├── header.php / footer.php          public layout
│   └── dash_header.php / dash_footer.php  dashboard layout (role sidebar)
├── api/                      JSON endpoints (login-required, role-guarded)
│   ├── resources.php  resource.php  availability.php  recommend.php
│   ├── bookings.php   notifications.php  complaints.php  announcements.php
│   ├── users.php      resource_admin.php  analytics.php  export.php
├── student/                  Student dashboard, browse/book, my bookings, notifications, report problem
├── faculty/                  Faculty dashboard, booking wizard, my bookings
├── admin/                    Admin dashboard, resources, bookings, users, complaints, announcements, analytics
├── assets/
│   ├── css/style.css         Design system (navy + gold theme)
│   └── js/                   main, auth, booking, recommend, admin, charts
├── database/
│   ├── generate_sql.php      Seed data generator
│   └── smartcampus.sql       Generated schema + seed
└── images/                   Uploaded images
```

---

## API quick reference (all JSON, login required)
| Endpoint                    | Methods / purpose |
|-----------------------------|-------------------|
| `/api/resources.php`        | `GET` list resources (filter by type/status/available) |
| `/api/resource.php?id=`     | `GET` resource details + slots for a date |
| `/api/availability.php`     | `GET` slot availability for a resource + date |
| `/api/recommend.php`        | `POST` ranked alternatives for a request |
| `/api/bookings.php`         | `GET` list, `POST` create / cancel / status / recommend_accept |
| `/api/notifications.php`    | `GET` list, `POST` mark read / read all |
| `/api/complaints.php`       | `GET` list, `POST` create / status |
| `/api/announcements.php`    | `GET` list, `POST` create (admin) / status |
| `/api/users.php`            | `GET` list, `POST` toggle / update / create (admin) |
| `/api/resource_admin.php`   | `POST` create / update / toggle / delete (admin) |
| `/api/analytics.php`        | `GET` overview / type / daily / hourly / resource / status (admin) |
| `/api/export.php`           | `GET` CSV downloads (admin) |

---

## Screens you should demo
1. **Home** → Log in as **admin** → **Admin dashboard**: charts, smart insights, KPIs.
2. **Admin → Resources**: add a resource, edit, toggle, delete; use the type filter
   (auditoriums, hostels, cafeterias, health centres, guest house, amenities, …).
3. **Admin → Bookings**: approve a pending booking, reject one, see conflict guard.
4. **Admin → Analytics**: type/daily/hourly/resource/status charts + CSV export.
5. Log in as **faculty** → **Book Resource wizard**: pick the busy **Commercial Computer
   Lab** with 45 students → the system recommends **Computer Lab 3** (best fit, ~38–41%
   utilization) with reasons → accept the recommendation.
6. Log in as **student** → browse resources → check live availability → book a study room →
   check **My bookings** + **Notifications** → report a problem.
7. **Admin** sees the new complaint/booking/notification and resolves/approves them.
