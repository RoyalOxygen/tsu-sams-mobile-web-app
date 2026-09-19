-- TSU-SAMS MySQL 8+ schema (fully normalized)
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS tsu_sams CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tsu_sams;

CREATE TABLE roles (
    id TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    slug VARCHAR(32) NOT NULL UNIQUE,
    name VARCHAR(64) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE users (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    role_id TINYINT UNSIGNED NOT NULL,
    username VARCHAR(80) NOT NULL UNIQUE,
    email VARCHAR(160) NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(160) NOT NULL,
    phone VARCHAR(32) NULL,
    status ENUM('pending','active','suspended','rejected') NOT NULL DEFAULT 'pending',
    last_login_at DATETIME NULL,
    last_login_ip VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB;
CREATE INDEX idx_users_role_status ON users(role_id, status);

CREATE TABLE faculties (
    id SMALLINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(16) NOT NULL UNIQUE,
    name VARCHAR(160) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE departments (
    id SMALLINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    faculty_id SMALLINT UNSIGNED NOT NULL,
    code VARCHAR(16) NOT NULL UNIQUE,
    name VARCHAR(160) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_dept_faculty FOREIGN KEY (faculty_id) REFERENCES faculties(id)
) ENGINE=InnoDB;

CREATE TABLE students (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNSIGNED NULL UNIQUE,
    matric_no VARCHAR(40) NOT NULL UNIQUE,
    first_name VARCHAR(80) NOT NULL,
    last_name VARCHAR(80) NOT NULL,
    other_name VARCHAR(80) NULL,
    gender ENUM('Male','Female') NOT NULL,
    faculty_id SMALLINT UNSIGNED NOT NULL,
    department_id SMALLINT UNSIGNED NOT NULL,
    level SMALLINT UNSIGNED NOT NULL,
    phone VARCHAR(32) NULL,
    email VARCHAR(160) NULL,
    passport_path VARCHAR(255) NULL,
    enrollment_status ENUM('preloaded','photo','face','paid','registered','active') NOT NULL DEFAULT 'preloaded',
    is_imported TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_students_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_students_faculty FOREIGN KEY (faculty_id) REFERENCES faculties(id),
    CONSTRAINT fk_students_dept FOREIGN KEY (department_id) REFERENCES departments(id)
) ENGINE=InnoDB;
CREATE INDEX idx_students_faculty_dept_level ON students(faculty_id, department_id, level);

CREATE TABLE lecturers (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    staff_no VARCHAR(40) NOT NULL UNIQUE,
    title VARCHAR(32) NULL,
    faculty_id SMALLINT UNSIGNED NOT NULL,
    department_id SMALLINT UNSIGNED NOT NULL,
    rank VARCHAR(80) NULL,
    approved_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_lecturers_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_lecturers_faculty FOREIGN KEY (faculty_id) REFERENCES faculties(id),
    CONSTRAINT fk_lecturers_dept FOREIGN KEY (department_id) REFERENCES departments(id)
) ENGINE=InnoDB;

CREATE TABLE courses (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    lecturer_id INT UNSIGNED NOT NULL,
    code VARCHAR(16) NOT NULL,
    title VARCHAR(200) NOT NULL,
    faculty_id SMALLINT UNSIGNED NOT NULL,
    department_id SMALLINT UNSIGNED NOT NULL,
    level SMALLINT UNSIGNED NOT NULL,
    semester ENUM('Harmattan','Rain') NOT NULL,
    academic_session VARCHAR(16) NOT NULL,
    unit TINYINT UNSIGNED NOT NULL DEFAULT 3,
    status ENUM('active','archived') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_course_session (code, academic_session, semester),
    CONSTRAINT fk_courses_lecturer FOREIGN KEY (lecturer_id) REFERENCES lecturers(id),
    CONSTRAINT fk_courses_faculty FOREIGN KEY (faculty_id) REFERENCES faculties(id),
    CONSTRAINT fk_courses_dept FOREIGN KEY (department_id) REFERENCES departments(id)
) ENGINE=InnoDB;

CREATE TABLE course_registrations (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    student_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    academic_session VARCHAR(16) NOT NULL,
    semester ENUM('Harmattan','Rain') NOT NULL,
    registered_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_reg (student_id, course_id, academic_session, semester),
    CONSTRAINT fk_reg_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_reg_course FOREIGN KEY (course_id) REFERENCES courses(id)
) ENGINE=InnoDB;

CREATE TABLE venues (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(160) NOT NULL,
    building_name VARCHAR(160) NOT NULL,
    latitude DECIMAL(10,7) NOT NULL,
    longitude DECIMAL(10,7) NOT NULL,
    radius INT UNSIGNED NOT NULL DEFAULT 100,
    capacity INT UNSIGNED NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    captured_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_venues_user FOREIGN KEY (captured_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE attendance_sessions (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    course_id INT UNSIGNED NOT NULL,
    venue_id INT UNSIGNED NOT NULL,
    lecturer_id INT UNSIGNED NOT NULL,
    session_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('scheduled','open','closed') NOT NULL DEFAULT 'scheduled',
    opened_at DATETIME NULL,
    closed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_as_course FOREIGN KEY (course_id) REFERENCES courses(id),
    CONSTRAINT fk_as_venue FOREIGN KEY (venue_id) REFERENCES venues(id),
    CONSTRAINT fk_as_lecturer FOREIGN KEY (lecturer_id) REFERENCES lecturers(id)
) ENGINE=InnoDB;
CREATE INDEX idx_as_status_date ON attendance_sessions(status, session_date);

CREATE TABLE attendance_records (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    session_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    latitude DECIMAL(10,7) NOT NULL,
    longitude DECIMAL(10,7) NOT NULL,
    distance_meters DECIMAL(10,2) NOT NULL,
    face_score DECIMAL(8,5) NOT NULL,
    status ENUM('present','late') NOT NULL DEFAULT 'present',
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_attendance (session_id, student_id),
    CONSTRAINT fk_ar_session FOREIGN KEY (session_id) REFERENCES attendance_sessions(id),
    CONSTRAINT fk_ar_student FOREIGN KEY (student_id) REFERENCES students(id)
) ENGINE=InnoDB;

CREATE TABLE face_profiles (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    student_id INT UNSIGNED NOT NULL UNIQUE,
    descriptor_enc MEDIUMTEXT NOT NULL,
    descriptor_hash CHAR(64) NOT NULL,
    sample_path VARCHAR(255) NULL,
    enrolled_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    CONSTRAINT fk_face_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE payments (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    student_id INT UNSIGNED NOT NULL,
    type ENUM('enrollment','face_update') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'NGN',
    status ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
    reference VARCHAR(64) NOT NULL UNIQUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    paid_at DATETIME NULL,
    CONSTRAINT fk_pay_student FOREIGN KEY (student_id) REFERENCES students(id)
) ENGINE=InnoDB;

CREATE TABLE transactions (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    payment_id INT UNSIGNED NOT NULL,
    channel VARCHAR(32) NOT NULL DEFAULT 'demo_gateway',
    provider_ref VARCHAR(80) NULL,
    amount DECIMAL(12,2) NOT NULL,
    status ENUM('initiated','success','failed') NOT NULL,
    raw_payload TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_txn_payment FOREIGN KEY (payment_id) REFERENCES payments(id)
) ENGINE=InnoDB;

CREATE TABLE settings (
    id SMALLINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(80) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNSIGNED NULL,
    action VARCHAR(80) NOT NULL,
    entity VARCHAR(80) NULL,
    entity_id VARCHAR(40) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    meta TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE INDEX idx_audit_action ON audit_logs(action, created_at);

CREATE TABLE activity_logs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNSIGNED NULL,
    event VARCHAR(80) NOT NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE login_attempts (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    identifier VARCHAR(120) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    success TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE INDEX idx_login_attempts ON login_attempts(identifier, ip_address, created_at);
CREATE INDEX idx_lecturers_scope ON lecturers(faculty_id, department_id);
CREATE INDEX idx_courses_lecturer ON courses(lecturer_id);
CREATE INDEX idx_courses_scope ON courses(faculty_id, department_id, level, semester, academic_session);
CREATE INDEX idx_reg_student ON course_registrations(student_id, academic_session, semester);
CREATE INDEX idx_reg_course ON course_registrations(course_id);
CREATE INDEX idx_sessions_course ON attendance_sessions(course_id);
CREATE INDEX idx_sessions_lecturer ON attendance_sessions(lecturer_id, status);
CREATE INDEX idx_records_student ON attendance_records(student_id, recorded_at);
CREATE INDEX idx_records_recorded ON attendance_records(recorded_at);
CREATE INDEX idx_payments_student ON payments(student_id, status);
CREATE INDEX idx_transactions_payment ON transactions(payment_id);
CREATE INDEX idx_audit_user ON audit_logs(user_id, created_at);

SET FOREIGN_KEY_CHECKS = 1;
