# TSU-SAMS

Taraba State University Smart Attendance Management System.

PHP 8, PDO, Bootstrap 5.3, vanilla JavaScript, InsightFace ArcFace (FastAPI). Facial recognition, GPS geofencing, RBAC.

## Portals

- Student: enroll, face login, take attendance, history, statistics, profile
- Lecturer: register, courses, open/close sessions, records, export
- Administrator: students, faces, lecturers, venues, payments, reports, audit logs

## Quick start

```bash
php -S 0.0.0.0:8080 -t public public/index.php

```bash
cd python
python3 -m venv venv
source venv/bin/activate or .\venv\Scripts\Activate.ps1
pip install -r requirements.txt
uvicorn main:app --host 0.0.0.0 --port 8000
```

Open the site, then sign in as `admin` / `Admin@TSU2025`.

Full steps: `docs/INSTALLATION.md`. Security notes: `docs/SECURITY.md`.
Schema: `database/schema.mysql.sql` and `database/schema.sqlite.sql`.
Sample data: `database/seed.sqlite.sql`, or run `php database/seed.php` (idempotent).
