# TSU-SAMS Architecture

## Folder structure

```
config.php
index.php                 XAMPP redirect into public/
app/
  bootstrap.php
  Helpers.php
  Database.php
  Security.php
  Auth.php
  Audit.php
  Router.php
  View.php
  Controllers/            including CbtSyncController for CBT hub pull
  Services/               Face, GPS, payments, installer, CMS
database/
  schema.mysql.sql
  schema.sqlite.sql
  sample_students.csv
public/                   Document root
  index.php
  assets/css|js
  uploads/
views/                    PHP templates
storage/                  SQLite file, logs, private face blobs
docs/
```

## Request flow

Browser -> `public/index.php` -> Router -> Controller (RBAC) -> Service / PDO -> View.

Attendance POST: CSRF -> session open -> course registration -> InsightFace ArcFace match (`python/` FastAPI on :9000) -> haversine geofence -> insert `attendance_records` -> auto logout.

Face enrollment: camera JPEGs -> PHP `FaceService::enroll` -> `POST /enroll` -> encrypted 512-d template in `face_profiles` plus `python/data/enrollments.json`.

CBT hub: `GET /api/cbt-sync/students` (token auth) decrypts templates and base64-encodes passports. See `docs/CBT_INTEGRATION.md`.
