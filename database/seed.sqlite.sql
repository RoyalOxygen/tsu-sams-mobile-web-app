-- ============================================================================
-- TSU-SAMS first-hand (sample) data for SQLite
-- ============================================================================
-- Target  : SQLite (default driver in config.php)
-- Schema  : database/schema.sqlite.sql
-- Usage   : sqlite3 storage/tsu_sams.sqlite < database/seed.sqlite.sql
--
-- The script is idempotent: every row is inserted with an explicit primary key
-- and uses INSERT OR IGNORE, so re-running it never duplicates data.
--
-- Demo credentials (password -> bcrypt hash below):
--   admin                Admin@TSU2025
--   TSU/STF/18/0429      Lecturer@123   (active lecturer)
--   TSU/STF/19/1102      Lecturer@123   (pending approval)
--   TSU/SCI/21/04882     Student@123    (active demo student)
-- ============================================================================

PRAGMA foreign_keys = ON;

BEGIN TRANSACTION;

-- ----------------------------------------------------------------------------
-- Roles
-- ----------------------------------------------------------------------------
INSERT OR IGNORE INTO roles (id, slug, name, created_at) VALUES
    (1, 'admin',    'Administrator', datetime('now')),
    (2, 'lecturer', 'Lecturer',      datetime('now')),
    (3, 'student',  'Student',       datetime('now'));

-- ----------------------------------------------------------------------------
-- Faculties
-- ----------------------------------------------------------------------------
INSERT OR IGNORE INTO faculties (id, code, name, created_at) VALUES
    (1, 'SCI', 'Faculty of Science',                       datetime('now')),
    (2, 'EDU', 'Faculty of Education',                     datetime('now')),
    (3, 'ART', 'Faculty of Arts',                          datetime('now')),
    (4, 'AGR', 'Faculty of Agriculture',                   datetime('now')),
    (5, 'SOC', 'Faculty of Social & Management Sciences',  datetime('now'));

-- ----------------------------------------------------------------------------
-- Departments
-- ----------------------------------------------------------------------------
INSERT OR IGNORE INTO departments (id, faculty_id, code, name, created_at) VALUES
    (1, 1, 'CSC', 'Computer Science',         datetime('now')),
    (2, 1, 'MTH', 'Mathematics',              datetime('now')),
    (3, 1, 'PHY', 'Physics',                  datetime('now')),
    (4, 2, 'EDU', 'Educational Foundations',  datetime('now')),
    (5, 3, 'ENG', 'English Language',         datetime('now')),
    (6, 4, 'AGR', 'Crop Science',             datetime('now')),
    (7, 5, 'ACC', 'Accounting',               datetime('now'));

-- ----------------------------------------------------------------------------
-- Users
-- ----------------------------------------------------------------------------
INSERT OR IGNORE INTO users (id, role_id, username, email, password_hash, full_name, phone, status, created_at) VALUES
    (1, 1, 'admin',              'ict@tsuniversity.edu.ng',          '$2y$12$HemoZwWJX1fM4JphSbWOReEWhNPBNkKt9eCJd6jmL9lABnLZiI4pe', 'Prof. Tunde Adewale', '08030000001', 'active',  datetime('now')),
    (2, 2, 'TSU/STF/18/0429',    'aliyu.bello@tsuniversity.edu.ng',  '$2y$12$MFErk/smu0BGDZd8qgK4re.foxeF1qn49aCl3iDpelJ1jw9aLaCNa', 'Dr. Aliyu Bello',     '08031234567', 'active',  datetime('now')),
    (3, 2, 'TSU/STF/19/1102',    'aisha.musa@tsuniversity.edu.ng',   '$2y$12$MFErk/smu0BGDZd8qgK4re.foxeF1qn49aCl3iDpelJ1jw9aLaCNa', 'Dr. Aisha Musa',      '08039876543', 'pending', datetime('now')),
    (4, 3, 'TSU/SCI/21/04882',   'ibrahim.danladi@student.tsuniversity.edu.ng', '$2y$12$pQozZ704eoYCHu6rMuzKuueELlRBVoJbS/1tMO5TQcgBMf/Jsx3Re', 'Ibrahim Danladi', '08041112233', 'active', datetime('now'));

-- ----------------------------------------------------------------------------
-- Lecturers
-- ----------------------------------------------------------------------------
INSERT OR IGNORE INTO lecturers (id, user_id, staff_no, title, faculty_id, department_id, rank, approved_at, created_at) VALUES
    (1, 2, 'TSU/STF/18/0429', 'Dr.', 1, 1, 'Senior Lecturer', datetime('now'), datetime('now')),
    (2, 3, 'TSU/STF/19/1102', 'Dr.', 1, 2, 'Lecturer I',      NULL,            datetime('now'));

-- ----------------------------------------------------------------------------
-- Students (preloaded registry list; only the demo student has a login)
-- ----------------------------------------------------------------------------
INSERT OR IGNORE INTO students (id, user_id, matric_no, first_name, last_name, gender, faculty_id, department_id, level, phone, email, enrollment_status, is_imported, created_at) VALUES
    (1,  4,    'TSU/SCI/21/04882', 'Ibrahim',  'Danladi',   'Male',   1, 1, 300, '08041112233', 'ibrahim.danladi@student.tsuniversity.edu.ng',  'active',    1, datetime('now')),
    (2,  NULL, 'TSU/SCI/22/03110', 'Fatima',   'Abdullahi', 'Female', 1, 1, 200, '08042223344', 'fatima.abdullahi@student.tsuniversity.edu.ng', 'preloaded', 1, datetime('now')),
    (3,  NULL, 'TSU/SCI/20/01776', 'Chinedu',  'Okafor',    'Male',   1, 2, 400, '08043334455', 'chinedu.okafor@student.tsuniversity.edu.ng',   'preloaded', 1, datetime('now')),
    (4,  NULL, 'TSU/SCI/23/05501', 'Amina',    'Yusuf',     'Female', 1, 3, 100, '08044445566', 'amina.yusuf@student.tsuniversity.edu.ng',      'preloaded', 1, datetime('now')),
    (5,  NULL, 'TSU/EDU/21/01220', 'Blessing', 'Tanimu',    'Female', 2, 4, 300, '08045556677', 'blessing.tanimu@student.tsuniversity.edu.ng',  'preloaded', 1, datetime('now')),
    (6,  NULL, 'TSU/ART/22/00881', 'Samuel',   'Nyame',     'Male',   3, 5, 200, '08046667788', 'samuel.nyame@student.tsuniversity.edu.ng',     'preloaded', 1, datetime('now')),
    (7,  NULL, 'TSU/AGR/21/00994', 'Hauwa',    'Suleiman',  'Female', 4, 6, 300, '08047778899', 'hauwa.suleiman@student.tsuniversity.edu.ng',   'preloaded', 1, datetime('now')),
    (8,  NULL, 'TSU/SOC/20/02145', 'Peter',    'Agbu',      'Male',   5, 7, 400, '08048889900', 'peter.agbu@student.tsuniversity.edu.ng',       'preloaded', 1, datetime('now')),
    (9,  NULL, 'TSU/SCI/21/04910', 'Zainab',   'Mohammed',  'Female', 1, 1, 300, '08049990011', 'zainab.mohammed@student.tsuniversity.edu.ng',  'preloaded', 1, datetime('now')),
    (10, NULL, 'TSU/SCI/22/03333', 'Emmanuel', 'Kefas',     'Male',   1, 1, 200, '08040001122', 'emmanuel.kefas@student.tsuniversity.edu.ng',   'preloaded', 1, datetime('now')),
    (11, NULL, 'TSU/SCI/23/06120', 'Grace',    'Adi',       'Female', 1, 2, 100, '08041112200', 'grace.adi@student.tsuniversity.edu.ng',        'preloaded', 1, datetime('now')),
    (12, NULL, 'TSU/EDU/22/01440', 'John',     'Tari',      'Male',   2, 4, 200, '08042220011', 'john.tari@student.tsuniversity.edu.ng',        'preloaded', 1, datetime('now'));

-- ----------------------------------------------------------------------------
-- Venues (coordinates around the TSU Jalingo campus)
-- ----------------------------------------------------------------------------
INSERT OR IGNORE INTO venues (id, name, building_name, latitude, longitude, radius, capacity, is_active, captured_by, created_at) VALUES
    (1, 'Lecture Theatre A', 'Faculty of Science Complex', 8.8932000, 11.3596000, 100, 250, 1, 1, datetime('now')),
    (2, 'ICT Lab 2',         'ICT Directorate Block',      8.8941000, 11.3602000,  80,  80, 1, 1, datetime('now')),
    (3, 'Lecture Hall B',    'Faculty of Education',       8.8924000, 11.3588000, 120, 180, 1, 1, datetime('now'));

-- ----------------------------------------------------------------------------
-- Courses (2024/2025 Harmattan)
-- ----------------------------------------------------------------------------
INSERT OR IGNORE INTO courses (id, lecturer_id, code, title, faculty_id, department_id, level, semester, academic_session, unit, status, created_at) VALUES
    (1, 1, 'CSC 101', 'Introduction to Computing',        1, 1, 100, 'Harmattan', '2024/2025', 2, 'active', datetime('now')),
    (2, 1, 'CSC 205', 'Object Oriented Programming',       1, 1, 200, 'Harmattan', '2024/2025', 3, 'active', datetime('now')),
    (3, 1, 'CSC 301', 'Data Communications & Networks',    1, 1, 300, 'Harmattan', '2024/2025', 3, 'active', datetime('now')),
    (4, 1, 'CSC 311', 'Operating Systems',                 1, 1, 300, 'Harmattan', '2024/2025', 3, 'active', datetime('now')),
    (5, 1, 'MTH 201', 'Mathematical Methods I',            1, 2, 200, 'Harmattan', '2024/2025', 3, 'active', datetime('now'));

-- ----------------------------------------------------------------------------
-- Course registrations for the demo student
-- ----------------------------------------------------------------------------
INSERT OR IGNORE INTO course_registrations (id, student_id, course_id, academic_session, semester, registered_at) VALUES
    (1, 1, 3, '2024/2025', 'Harmattan', datetime('now')),
    (2, 1, 4, '2024/2025', 'Harmattan', datetime('now'));

-- ----------------------------------------------------------------------------
-- Attendance sessions
--   id 1,2 : past lectures (closed)  -> gives the demo student history
--   id 3   : today's lecture (open)  -> ready for live check-in demo
--   id 4   : tomorrow (scheduled)
-- ----------------------------------------------------------------------------
INSERT OR IGNORE INTO attendance_sessions (id, course_id, venue_id, lecturer_id, session_date, start_time, end_time, status, opened_at, closed_at, created_at) VALUES
    (1, 3, 1, 1, date('now', '-7 day'), '08:00:00', '10:00:00', 'closed',    datetime('now', '-7 day'), datetime('now', '-7 day'), datetime('now', '-7 day')),
    (2, 4, 3, 1, date('now', '-3 day'), '12:00:00', '14:00:00', 'closed',    datetime('now', '-3 day'), datetime('now', '-3 day'), datetime('now', '-3 day')),
    (3, 3, 1, 1, date('now'),           '08:00:00', '10:00:00', 'open',      datetime('now'),           NULL,                      datetime('now')),
    (4, 2, 2, 1, date('now', '+1 day'), '14:00:00', '16:00:00', 'scheduled', NULL,                      NULL,                      datetime('now'));

-- ----------------------------------------------------------------------------
-- Attendance records for the demo student
-- ----------------------------------------------------------------------------
INSERT OR IGNORE INTO attendance_records (id, session_id, student_id, latitude, longitude, distance_meters, face_score, status, ip_address, user_agent, recorded_at) VALUES
    (1, 1, 1, 8.8932000, 11.3596000, 12.4, 0.986, 'present', '127.0.0.1', 'seed.sqlite.sql', datetime('now', '-7 day')),
    (2, 2, 1, 8.8932000, 11.3596000, 35.1, 0.961, 'late',    '127.0.0.1', 'seed.sqlite.sql', datetime('now', '-3 day'));

-- ----------------------------------------------------------------------------
-- Face profile template for the demo student
--   descriptor = JSON array of 512 x 0.1, AES-256-CBC encrypted with the
--   default app key from config.php (app.key).
-- ----------------------------------------------------------------------------
INSERT OR IGNORE INTO face_profiles (id, student_id, descriptor_enc, descriptor_hash, sample_path, enrolled_at) VALUES
    (1, 1, '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', '6e80c14b8f891391339bf53bd610974b2818cc563a3d4ce35a5933abf1fd5020', NULL, datetime('now'));

-- ----------------------------------------------------------------------------
-- Settings
-- ----------------------------------------------------------------------------
INSERT OR IGNORE INTO settings (id, setting_key, setting_value) VALUES
    (1, 'payments_enabled', '0'),
    (2, 'enrollment_fee',   '2500'),
    (3, 'face_update_fee',  '1500'),
    (9, 'enrollment_fee_enabled', '0'),
    (10, 'face_update_fee_enabled', '0'),
    (4, 'default_radius',   '100'),
    (5, 'academic_session', '2024/2025'),
    (6, 'semester',         'Harmattan'),
    (7, 'exam_threshold',   '75'),
    (8, 'institution',      'Taraba State University, Jalingo');

-- ----------------------------------------------------------------------------
-- Payments and transactions (demo student paid the enrollment fee)
-- ----------------------------------------------------------------------------
INSERT OR IGNORE INTO payments (id, student_id, type, amount, currency, status, reference, created_at, paid_at) VALUES
    (1, 1, 'enrollment', 2500, 'NGN', 'paid', 'TSU-ENR-2024-0001', datetime('now', '-20 day'), datetime('now', '-20 day'));

INSERT OR IGNORE INTO transactions (id, payment_id, channel, provider_ref, amount, status, raw_payload, created_at) VALUES
    (1, 1, 'demo_gateway', 'SEED-TXN-0001', 2500, 'success', '{"source":"seed.sqlite.sql"}', datetime('now', '-20 day'));

-- ----------------------------------------------------------------------------
-- Audit trail samples
-- ----------------------------------------------------------------------------
INSERT OR IGNORE INTO audit_logs (id, user_id, action, entity, entity_id, ip_address, user_agent, meta, created_at) VALUES
    (1, 1, 'system.seed',       'database',             NULL, '127.0.0.1', 'seed.sqlite.sql', '{"source":"seed.sqlite.sql"}',                 datetime('now')),
    (2, 1, 'attendance.record', 'attendance_sessions',  '1',  '127.0.0.1', 'seed.sqlite.sql', '{"student":"TSU/SCI/21/04882","status":"present"}', datetime('now', '-7 day'));

COMMIT;
