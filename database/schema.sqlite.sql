PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    slug TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    role_id INTEGER NOT NULL,
    username TEXT NOT NULL UNIQUE,
    email TEXT UNIQUE,
    password_hash TEXT NOT NULL,
    full_name TEXT NOT NULL,
    phone TEXT,
    status TEXT NOT NULL DEFAULT 'pending',
    last_login_at TEXT,
    last_login_ip TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);
CREATE INDEX IF NOT EXISTS idx_users_role_status ON users(role_id, status);

CREATE TABLE IF NOT EXISTS faculties (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS departments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    faculty_id INTEGER NOT NULL,
    code TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (faculty_id) REFERENCES faculties(id)
);

CREATE TABLE IF NOT EXISTS students (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER UNIQUE,
    matric_no TEXT NOT NULL UNIQUE,
    first_name TEXT NOT NULL,
    last_name TEXT NOT NULL,
    other_name TEXT,
    gender TEXT NOT NULL,
    faculty_id INTEGER NOT NULL,
    department_id INTEGER NOT NULL,
    level INTEGER NOT NULL,
    phone TEXT,
    email TEXT,
    passport_path TEXT,
    enrollment_status TEXT NOT NULL DEFAULT 'preloaded',
    is_imported INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (faculty_id) REFERENCES faculties(id),
    FOREIGN KEY (department_id) REFERENCES departments(id)
);

CREATE TABLE IF NOT EXISTS lecturers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL UNIQUE,
    staff_no TEXT NOT NULL UNIQUE,
    title TEXT,
    faculty_id INTEGER NOT NULL,
    department_id INTEGER NOT NULL,
    rank TEXT,
    approved_at TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (faculty_id) REFERENCES faculties(id),
    FOREIGN KEY (department_id) REFERENCES departments(id)
);

CREATE TABLE IF NOT EXISTS courses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    lecturer_id INTEGER NOT NULL,
    code TEXT NOT NULL,
    title TEXT NOT NULL,
    faculty_id INTEGER NOT NULL,
    department_id INTEGER NOT NULL,
    level INTEGER NOT NULL,
    semester TEXT NOT NULL,
    academic_session TEXT NOT NULL,
    unit INTEGER NOT NULL DEFAULT 3,
    status TEXT NOT NULL DEFAULT 'active',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT,
    UNIQUE (code, academic_session, semester),
    FOREIGN KEY (lecturer_id) REFERENCES lecturers(id),
    FOREIGN KEY (faculty_id) REFERENCES faculties(id),
    FOREIGN KEY (department_id) REFERENCES departments(id)
);

CREATE TABLE IF NOT EXISTS course_registrations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id INTEGER NOT NULL,
    course_id INTEGER NOT NULL,
    academic_session TEXT NOT NULL,
    semester TEXT NOT NULL,
    registered_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (student_id, course_id, academic_session, semester),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id)
);

CREATE TABLE IF NOT EXISTS venues (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    building_name TEXT NOT NULL,
    latitude REAL NOT NULL,
    longitude REAL NOT NULL,
    radius INTEGER NOT NULL DEFAULT 100,
    capacity INTEGER,
    is_active INTEGER NOT NULL DEFAULT 1,
    captured_by INTEGER,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT,
    FOREIGN KEY (captured_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS attendance_sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    course_id INTEGER NOT NULL,
    venue_id INTEGER NOT NULL,
    lecturer_id INTEGER NOT NULL,
    session_date TEXT NOT NULL,
    start_time TEXT NOT NULL,
    end_time TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'scheduled',
    opened_at TEXT,
    closed_at TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id),
    FOREIGN KEY (venue_id) REFERENCES venues(id),
    FOREIGN KEY (lecturer_id) REFERENCES lecturers(id)
);

CREATE TABLE IF NOT EXISTS attendance_records (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id INTEGER NOT NULL,
    student_id INTEGER NOT NULL,
    latitude REAL NOT NULL,
    longitude REAL NOT NULL,
    distance_meters REAL NOT NULL,
    face_score REAL NOT NULL,
    status TEXT NOT NULL DEFAULT 'present',
    ip_address TEXT,
    user_agent TEXT,
    recorded_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (session_id, student_id),
    FOREIGN KEY (session_id) REFERENCES attendance_sessions(id),
    FOREIGN KEY (student_id) REFERENCES students(id)
);

CREATE TABLE IF NOT EXISTS face_profiles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id INTEGER NOT NULL UNIQUE,
    descriptor_enc TEXT NOT NULL,
    descriptor_hash TEXT NOT NULL,
    sample_path TEXT,
    enrolled_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id INTEGER NOT NULL,
    type TEXT NOT NULL,
    amount REAL NOT NULL,
    currency TEXT NOT NULL DEFAULT 'NGN',
    status TEXT NOT NULL DEFAULT 'pending',
    reference TEXT NOT NULL UNIQUE,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    paid_at TEXT,
    FOREIGN KEY (student_id) REFERENCES students(id)
);

CREATE TABLE IF NOT EXISTS transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    payment_id INTEGER NOT NULL,
    channel TEXT NOT NULL DEFAULT 'demo_gateway',
    provider_ref TEXT,
    amount REAL NOT NULL,
    status TEXT NOT NULL,
    raw_payload TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES payments(id)
);

CREATE TABLE IF NOT EXISTS settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    setting_key TEXT NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    updated_at TEXT
);

CREATE TABLE IF NOT EXISTS audit_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    action TEXT NOT NULL,
    entity TEXT,
    entity_id TEXT,
    ip_address TEXT,
    user_agent TEXT,
    meta TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS activity_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    event TEXT NOT NULL,
    details TEXT,
    ip_address TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS login_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    identifier TEXT NOT NULL,
    ip_address TEXT NOT NULL,
    success INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_login_attempts ON login_attempts(identifier, ip_address, created_at);
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_students_scope ON students(faculty_id, department_id, level);
CREATE INDEX IF NOT EXISTS idx_lecturers_scope ON lecturers(faculty_id, department_id);
CREATE INDEX IF NOT EXISTS idx_courses_lecturer ON courses(lecturer_id);
CREATE INDEX IF NOT EXISTS idx_courses_scope ON courses(faculty_id, department_id, level, semester, academic_session);
CREATE INDEX IF NOT EXISTS idx_reg_student ON course_registrations(student_id, academic_session, semester);
CREATE INDEX IF NOT EXISTS idx_reg_course ON course_registrations(course_id);
CREATE INDEX IF NOT EXISTS idx_sessions_course ON attendance_sessions(course_id);
CREATE INDEX IF NOT EXISTS idx_sessions_lecturer ON attendance_sessions(lecturer_id, status);
CREATE INDEX IF NOT EXISTS idx_sessions_status_date ON attendance_sessions(status, session_date);
CREATE INDEX IF NOT EXISTS idx_records_student ON attendance_records(student_id, recorded_at);
CREATE INDEX IF NOT EXISTS idx_records_recorded ON attendance_records(recorded_at);
CREATE INDEX IF NOT EXISTS idx_face_student ON face_profiles(student_id);
CREATE INDEX IF NOT EXISTS idx_payments_student ON payments(student_id, status);
CREATE INDEX IF NOT EXISTS idx_transactions_payment ON transactions(payment_id);
CREATE INDEX IF NOT EXISTS idx_audit_user ON audit_logs(user_id, created_at);
CREATE INDEX IF NOT EXISTS idx_audit_action ON audit_logs(action, created_at);
