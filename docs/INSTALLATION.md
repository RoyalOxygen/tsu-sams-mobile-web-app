# TSU-SAMS Installation Guide

Taraba State University Smart Attendance Management System.

## Requirements

- PHP 8.1 or later (PHP 8.2+ recommended)
- PDO with MySQL or SQLite
- OpenSSL, mbstring, fileinfo
- Apache with mod_rewrite (XAMPP on Windows) or PHP built-in server
- HTTPS in production (camera and GPS require a secure context on most browsers)
- Python 3.10+ for the InsightFace ArcFace face service (`python/`)

## XAMPP (Windows) — MySQL

1. Copy this project to `C:\xampp\htdocs\tsu-sams`
2. Create database `tsu_sams` in phpMyAdmin
3. Import `database/schema.mysql.sql`
4. Edit `config.php`:

```php
'db' => [
    'driver' => 'mysql',
    'host' => '127.0.0.1',
    'port' => 3306,
    'name' => 'tsu_sams',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8mb4',
],
```

5. Set `app.key` to a long random string
6. Create folders and grant write access:
   - `storage/`
   - `storage/logs/`
   - `storage/faces/`
   - `public/uploads/passports/`
   - `public/uploads/faces/`
7. Point the document root at `public/` (virtual host) or visit `http://localhost/tsu-sams/public/`
8. On first request with an empty database, seed data is inserted automatically when using SQLite. For MySQL, after importing the schema, visit the site once; if roles are empty the seeder runs.

If the seeder does not run on MySQL, copy demo accounts from `docs/SECURITY.md` and insert them manually, or switch `driver` to `sqlite` for a self-contained trial.

## SQLite (quick start)

Leave `config.php` on `driver => sqlite`. The file `storage/tsu_sams.sqlite` is created and seeded on first request.

```bash
php -S 0.0.0.0:8080 -t public public/index.php
```

To load the first-hand sample data directly into SQL (roles, faculties, departments, demo users, students, venues, courses, sessions, records, settings, payments):

```bash
# Create the schema first if the database file does not exist yet
sqlite3 storage/tsu_sams.sqlite < database/schema.sqlite.sql

# Insert the sample data (idempotent, safe to re-run)
sqlite3 storage/tsu_sams.sqlite < database/seed.sqlite.sql
```

The same dataset can be produced from PHP instead:

```bash
php database/seed.php
```

## Face service (required for enrollment and face login)

The PHP app does not extract embeddings in the browser. Camera stills are posted to PHP, which calls the FastAPI InsightFace service (`buffalo_l`, 512-d ArcFace).

```bash
cd python
SAMS_PORT=9000 ./start.sh
```

`config.php` `face.url` must match that port (default `http://127.0.0.1:9000`).

The first start downloads the `buffalo_l` model into `python/models/` (~300 MB). Keep the process running while students enroll or take attendance.

Docker alternative: build `python/Dockerfile` and set `face.url` to the container.

## Demo accounts (after seed)

- Administrator: `admin` / `Admin@TSU2025`
- Lecturer: `TSU/STF/18/0429` / `Lecturer@123`
- Students: complete Register with a preloaded matric, e.g. `TSU/SCI/21/04882`

## Student enrollment

1. Admin preloads or imports matric records
2. Student enters matric number
3. Confirms biodata
4. Uploads passport
5. Live face enrollment (camera)
6. Pays fee if payments are enabled
7. Registers department courses
8. Signs in with face or password

## Attendance

1. Lecturer opens a session for a course and venue
2. Student face-logs in
3. Selects the lecture
4. Browser captures GPS and live face
5. Server records attendance only if face matches, course is registered, session is open, and coordinates are inside the venue radius
6. Student is signed out automatically

## Permissions

Uploads and the SQLite file must be writable by the web server user.
