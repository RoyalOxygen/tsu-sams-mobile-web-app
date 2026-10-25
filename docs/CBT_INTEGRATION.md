# CBT hub integration

TSU-SAMS exports enrolled students, passport photos, decrypted ArcFace templates, and overall attendance rate so [CBT Pro](https://github.com/RoyalOxygen/CBT-SYSTEM) can pull them into the online exam hub.

## What SAMS exposes

`GET /api/cbt-sync/students`

Query:

- `token` — same value as **Admin > Settings > CBT export** (also accepted as `X-CBT-Sync-Token`)
- `since` — optional ISO datetime; only students or face profiles changed on or after this time
- `limit` — page size, 1–100 (default 50)
- `offset` — pagination offset

JSON:

```json
{
  "ok": true,
  "total": 1,
  "limit": 50,
  "offset": 0,
  "exam_threshold": 75,
  "students": [
    {
      "sams_student_id": 1,
      "matric_no": "TSU/SCI/21/04882",
      "first_name": "Ada",
      "last_name": "Okoro",
      "other_name": "",
      "gender": "Female",
      "email": "ada@example.com",
      "phone": "08000000000",
      "level": 200,
      "department_code": "CSC",
      "department_name": "Computer Science",
      "faculty_name": "Science",
      "status": "active",
      "enrollment_status": "active",
      "updated_at": "2025-10-25 12:00:00",
      "attendance_percent": 82.5,
      "attendance_present": 33,
      "attendance_total": 40,
      "exam_threshold": 75,
      "exam_eligible": true,
      "photo_b64": "...",
      "photo_mime": "image/jpeg",
      "face_descriptor": [0.1, 0.1]
    }
  ]
}
```

`face_descriptor` is the 512-d ArcFace vector from `face_profiles.descriptor_enc`, decrypted with `app.key`. Photos are base64 of `public/uploads/passports/...`.

Attendance rate is present records divided by open+closed sessions for registered courses. `exam_eligible` is true when `attendance_percent >= exam_threshold` (Admin > Settings, default 75).

GET is token-auth only. CSRF is not applied.

## SAMS setup

1. Sign in as administrator.
2. Open **Admin > Settings**.
3. Under **CBT export**, click **Regenerate** to create a 64-character hex token, then **Copy**.
4. Regenerating invalidates the previous token. Paste the new value into the CBT hub before the next pull.
5. Keep the token private; anyone with it can download student photos and face templates.

## CBT hub setup

On the CBT online hub (not an offline hall):

1. Admin > Settings > **Attendance (SAMS) sync**
2. SAMS base URL with no trailing slash, for example `http://127.0.0.1:8080`
3. The same sync token
4. Enable pull
5. Face API URL: InsightFace FastAPI (`buffalo_l`), usually `http://127.0.0.1:9000`
6. Enable **student face login** after templates have been imported
7. Click **Sync from SAMS now**, or run:

```bash
php bin/sams-sync.php
php bin/sams-sync.php --full
```

Hub-only. Offline halls receive students, photos, face profiles, and attendance fields through CBT hub/leaf sync.

After a successful pull, CBT stores `attendance_percent`, `attendance_present`, `attendance_total`, `exam_threshold`, and `exam_eligible` on each student. Admin > Students shows the rate and eligibility badge. The student dashboard shows overall attendance against the exam threshold.

## Exam capture

Faces enrolled through `exam-capture/` use the same `students` and `face_profiles` tables, so they are included in the next CBT pull.
