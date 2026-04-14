-- ============================================================
-- School ERP Database Schema
-- Version 2.0 - With Developer Role, Admissions & PayU
-- ============================================================
CREATE DATABASE IF NOT EXISTS school_erp;
USE school_erp;

-- Users (developer, admin, teacher, student)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('developer','admin','teacher','student') NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    profile_pic VARCHAR(255) DEFAULT 'default.png',
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- System Settings (for developer)
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_group VARCHAR(50) DEFAULT 'general',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Classes
CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(50) NOT NULL,
    section VARCHAR(10) NOT NULL,
    class_teacher_id INT,
    seats INT DEFAULT 40,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_class_section (class_name, section)
);

-- Subjects
CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_name VARCHAR(100) NOT NULL,
    subject_code VARCHAR(20) UNIQUE NOT NULL,
    class_id INT,
    teacher_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
);

-- Teachers
CREATE TABLE IF NOT EXISTS teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    employee_id VARCHAR(20) UNIQUE NOT NULL,
    qualification VARCHAR(150),
    experience_years INT DEFAULT 0,
    join_date DATE,
    salary DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Admissions (applications from students)
CREATE TABLE IF NOT EXISTS admissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admission_no VARCHAR(20) UNIQUE NOT NULL,
    applicant_name VARCHAR(100) NOT NULL,
    applicant_email VARCHAR(100) NOT NULL,
    applicant_phone VARCHAR(20) NOT NULL,
    date_of_birth DATE,
    gender ENUM('Male','Female','Other'),
    address TEXT,
    parent_name VARCHAR(100) NOT NULL,
    parent_phone VARCHAR(20) NOT NULL,
    parent_email VARCHAR(100),
    applying_class_id INT,
    previous_school VARCHAR(150),
    previous_marks DECIMAL(5,2),
    blood_group VARCHAR(5),
    status ENUM('pending','approved','rejected','enrolled') DEFAULT 'pending',
    admission_fee DECIMAL(10,2) DEFAULT 500.00,
    fee_status ENUM('unpaid','paid','refunded') DEFAULT 'unpaid',
    payment_txn_id VARCHAR(100),
    payment_date DATETIME,
    payment_method VARCHAR(50),
    remarks TEXT,
    user_id INT,     -- set after enrollment
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (applying_class_id) REFERENCES classes(id) ON DELETE SET NULL
);

-- PayU Payment Log
CREATE TABLE IF NOT EXISTS payment_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    txnid VARCHAR(100) NOT NULL,
    admission_id INT,
    amount DECIMAL(10,2),
    productinfo VARCHAR(200),
    firstname VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    status ENUM('initiated','success','failure','pending') DEFAULT 'initiated',
    payu_txnid VARCHAR(100),
    payu_status VARCHAR(50),
    payu_response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admission_id) REFERENCES admissions(id) ON DELETE SET NULL
);

-- Students
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    admission_id INT,
    roll_number VARCHAR(20) UNIQUE NOT NULL,
    class_id INT NOT NULL,
    parent_name VARCHAR(100),
    parent_phone VARCHAR(20),
    parent_email VARCHAR(100),
    date_of_birth DATE,
    gender ENUM('Male','Female','Other'),
    blood_group VARCHAR(5),
    admission_date DATE DEFAULT (CURRENT_DATE),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (admission_id) REFERENCES admissions(id) ON DELETE SET NULL
);

-- Attendance
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('Present','Absent','Late','Leave') DEFAULT 'Present',
    marked_by INT,
    remarks VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_att (student_id, date),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
);

-- Exams
CREATE TABLE IF NOT EXISTS exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_name VARCHAR(100) NOT NULL,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    exam_date DATE NOT NULL,
    total_marks INT NOT NULL DEFAULT 100,
    pass_marks INT NOT NULL DEFAULT 40,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

-- Marks
CREATE TABLE IF NOT EXISTS marks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    exam_id INT NOT NULL,
    marks_obtained DECIMAL(5,2) DEFAULT 0,
    grade VARCHAR(5),
    remarks VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_marks (student_id, exam_id),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
);

-- Fees
CREATE TABLE IF NOT EXISTS fees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    fee_type VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    due_date DATE NOT NULL,
    paid_date DATE,
    status ENUM('Pending','Paid','Overdue') DEFAULT 'Pending',
    payment_method VARCHAR(50),
    transaction_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Notices
CREATE TABLE IF NOT EXISTS notices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    target_role ENUM('all','developer','admin','teacher','student') DEFAULT 'all',
    published_by INT NOT NULL,
    is_published TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (published_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Timetable
CREATE TABLE IF NOT EXISTS timetable (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    teacher_id INT NOT NULL,
    day_of_week ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
);

-- ==========================
-- DEFAULT DATA
-- ==========================

-- Settings
INSERT INTO settings (setting_key, setting_value, setting_group) VALUES
('school_name', 'Bright Future Academy', 'general'),
('school_email', 'info@brightfuture.edu', 'general'),
('school_phone', '9876543200', 'general'),
('school_address', '123 Education Lane, Knowledge City - 400001', 'general'),
('school_logo', '', 'general'),
('academic_year', '2025-2026', 'general'),
('admission_fee', '500', 'admission'),
('payu_key', 'gtKFFx', 'payment'),
('payu_salt', 'eCwWELxi', 'payment'),
('payu_mode', 'test', 'payment'),
('currency', 'INR', 'payment');

-- Developer user (password: developer123)
INSERT INTO users (name, email, password, role, phone, address) VALUES
('System Developer', 'developer@school.com', '$2y$10$TKh8H1.PonurY9Vgr4/9euqmRqlJKKjt/HZOR6KFZ6v1GcCKQMp.2', 'developer', '9000000001', 'Developer HQ'),
-- Admin (password: admin123)
('School Admin', 'admin@school.com', '$2y$10$TKh8H1.PonurY9Vgr4/9euqmRqlJKKjt/HZOR6KFZ6v1GcCKQMp.2', 'admin', '9000000002', '123 School St'),
-- Teachers (password: password)
('Rajesh Kumar', 'rajesh@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', '9000000003', '456 Teacher Ave'),
('Priya Sharma', 'priya@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', '9000000004', '789 Teacher Blvd'),
-- Students (password: password)
('Arjun Patel', 'arjun@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '9000000005', '321 Student Rd'),
('Meera Singh', 'meera@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '9000000006', '654 Student Ln');

-- Classes
INSERT INTO classes (class_name, section, seats) VALUES
('Class 1','A',40),('Class 1','B',40),
('Class 2','A',40),('Class 3','A',40),
('Class 4','A',40),('Class 5','A',40),
('Class 6','A',40),('Class 7','A',40),
('Class 8','A',40),('Class 9','A',40),
('Class 10','A',40),
('Class 11','Science',35),('Class 11','Commerce',35),
('Class 12','Science',35),('Class 12','Commerce',35);

-- Teachers
INSERT INTO teachers (user_id, employee_id, qualification, experience_years, join_date, salary) VALUES
(3, 'EMP001', 'M.Sc Mathematics, B.Ed', 10, '2015-06-01', 55000.00),
(4, 'EMP002', 'M.A English, B.Ed', 6, '2019-07-15', 48000.00);

-- Students
INSERT INTO students (user_id, roll_number, class_id, parent_name, parent_phone, parent_email, date_of_birth, gender, blood_group) VALUES
(5, 'STU001', 1, 'Vikram Patel', '9000000010', 'vikram@email.com', '2015-03-15', 'Male', 'A+'),
(6, 'STU002', 1, 'Suresh Singh', '9000000011', 'suresh@email.com', '2015-07-22', 'Female', 'B+');

-- Subjects
INSERT INTO subjects (subject_name, subject_code, class_id, teacher_id) VALUES
('Mathematics', 'MATH01', 1, 1),
('English', 'ENG01', 1, 2),
('Science', 'SCI01', 1, 1),
('Social Studies', 'SS01', 1, 2),
('Hindi', 'HIN01', 1, NULL);

-- Sample Admission Applications
INSERT INTO admissions (admission_no, applicant_name, applicant_email, applicant_phone, date_of_birth, gender, address, parent_name, parent_phone, parent_email, applying_class_id, previous_school, blood_group, status, fee_status, admission_fee) VALUES
('ADM2026-001','Rohan Mehta','rohan@email.com','9111111101','2015-05-10','Male','12 Park Street, City','Amit Mehta','9111111100','amit@email.com',1,'City Public School','O+','pending','unpaid',500.00),
('ADM2026-002','Sneha Gupta','sneha@email.com','9111111102','2014-08-22','Female','45 Main Road, Town','Rakesh Gupta','9111111103','rakesh@email.com',3,'Green Valley School','AB+','approved','paid',500.00),
('ADM2026-003','Karan Verma','karan@email.com','9111111104','2013-11-15','Male','78 Lake View, Suburb','Mohan Verma','9111111105','mohan@email.com',4,'Sunrise Academy','B-','pending','paid',500.00);

-- Fees
INSERT INTO fees (student_id, fee_type, amount, due_date, status) VALUES
(1,'Tuition Fee',5000.00,'2026-04-30','Paid'),
(1,'Library Fee',500.00,'2026-04-30','Pending'),
(2,'Tuition Fee',5000.00,'2026-04-30','Pending'),
(2,'Library Fee',500.00,'2026-04-30','Pending');

-- Notices
INSERT INTO notices (title, content, target_role, published_by) VALUES
('Welcome to Academic Year 2025-26','Dear students and parents, welcome to the new academic year!','all',2),
('Annual Sports Day','Annual Sports Day on 15th April 2026. All students must participate.','all',2),
('Parent-Teacher Meeting','PTM scheduled for 20th April 2026 from 9 AM to 12 PM.','student',2);

-- Update admission_no sequence note:
-- Admission numbers are auto-generated as ADM{YEAR}-{seq} in PHP
