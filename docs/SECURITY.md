# TSU-SAMS Security Documentation

## Controls implemented

- PDO prepared statements for all queries (SQL injection prevention)
- CSRF tokens on POST forms and AJAX (`_csrf` / `X-CSRF-TOKEN`)
- XSS escaping via `e()` (htmlspecialchars, ENT_QUOTES, UTF-8)
- HttpOnly / SameSite session cookies, periodic session ID regeneration
- Bcrypt password hashing (cost 12)
- Login rate limiting (identifier + IP, default 8 failures / 15 minutes)
- Role-based access: Administrator, Lecturer, Student
- Image upload MIME and size validation (JPEG/PNG/WEBP, 3MB)
- Facial descriptors stored encrypted (AES-256-CBC) using `app.key`
- Audit logs and activity logs for authentication, attendance, and admin actions
- Security headers: X-Frame-Options, X-Content-Type-Options, Referrer-Policy, CSP

## Attendance gate (all must pass)

1. Authenticated student with enrolled face template
2. Course registration for the session course / semester / session year
3. Attendance session status = open
4. Student selected that lecture
5. InsightFace ArcFace cosine similarity >= 0.50 against the enrolled 512-d template (1:1 or 1:N)
6. Haversine distance to venue coordinates <= venue radius (default 100m)

## Facial data

- Browser captures JPEG stills only; no on-device embedding
- PHP forwards images to TSU-SAMS Face Service (InsightFace ArcFace `buffalo_l`)
- Enrollment uses `POST /enroll` (averaged 512-d embedding); login/attendance use `/verify` and `/compare`
- Optional JPEG snapshot is saved under `public/uploads/faces/` for admin review
- Descriptor ciphertext lives in `face_profiles.descriptor_enc`; the service also stores embeddings in `python/data/enrollments.json`
- Face service binds to localhost (default port 9000) and is not exposed as the public preview port
- Rotate `app.key` only with a planned re-encryption or re-enrollment

## Production hardening checklist

- Change default passwords immediately
- Set a unique 32+ character `app.key`
- Serve over HTTPS
- Disable `app.debug`
- Use MySQL 8 with least-privilege DB user
- Restrict `storage/` from web access (document root = `public/`)
- Connect a real payment gateway (Paystack/Remita); demo gateway is for trials only
- Back up `face_profiles` and `attendance_records` daily
- Review `audit_logs` for failed face and geofence attempts

## Default seed credentials (change after install)

| Role | Username | Password |
|------|----------|----------|
| Administrator | admin | Admin@TSU2025 |
| Lecturer (approved) | TSU/STF/18/0429 | Lecturer@123 |
| Lecturer (pending) | TSU/STF/19/1102 | Lecturer@123 |
