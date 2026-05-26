<?php
require_once __DIR__ . '/db.php';

// Run this once to create tables
$pdo = DB::get();

$pdo->exec("
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS report_courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    course_code VARCHAR(100) NOT NULL,
    course_name VARCHAR(255) DEFAULT '',
    course_length INT DEFAULT 16,
    weeks_loaded INT DEFAULT 0,
    last_upload TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
    UNIQUE KEY uq_report_course (report_id, course_code),
    INDEX idx_report (report_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS report_students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_course_id INT NOT NULL,
    student_ext_id VARCHAR(100) NOT NULL,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) DEFAULT '',
    student_data JSON NOT NULL,
    risk_score JSON DEFAULT NULL,
    sentiment JSON DEFAULT NULL,
    teacher_review JSON DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (report_course_id) REFERENCES report_courses(id) ON DELETE CASCADE,
    UNIQUE KEY uq_course_student (report_course_id, student_ext_id),
    INDEX idx_course (report_course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

echo json_encode(['success' => true, 'message' => 'All tables created successfully']);
