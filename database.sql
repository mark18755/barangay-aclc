-- ============================================
-- Barangay ACLC - Complaint System Database
-- Import this in phpMyAdmin > Import tab
-- ============================================

CREATE DATABASE IF NOT EXISTS barangay_db;
USE barangay_db;

DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS blotter_records;
DROP TABLE IF EXISTS complaints;
DROP TABLE IF EXISTS users;

-- Users (no username, login by email)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin','staff','resident') DEFAULT 'resident',
    address VARCHAR(255) DEFAULT '',
    contact VARCHAR(20) DEFAULT '',
    status ENUM('active','pending','disabled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Complaints
CREATE TABLE complaints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_number VARCHAR(10) NOT NULL UNIQUE,
    complainant_name VARCHAR(100) NOT NULL,
    complainant_address VARCHAR(255) DEFAULT '',
    complainant_contact VARCHAR(20) DEFAULT '',
    respondent_name VARCHAR(100) NOT NULL,
    respondent_address VARCHAR(255) DEFAULT '',
    incident_description TEXT NOT NULL,
    date_of_incident DATE NOT NULL,
    date_filed TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Pending','Ongoing','Resolved') DEFAULT 'Pending',
    notes TEXT DEFAULT NULL,
    filed_by VARCHAR(100) DEFAULT NULL
);

-- Blotter records
CREATE TABLE blotter_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT NOT NULL,
    blotter_number VARCHAR(10) NOT NULL UNIQUE,
    hearing_date DATE DEFAULT NULL,
    settlement TEXT DEFAULT NULL,
    remarks TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE
);

-- Contact messages
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_name VARCHAR(100) NOT NULL,
    sender_email VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- Default Accounts
-- admin@barangay.gov.ph  / admin123
-- staff@barangay.gov.ph  / staff123
-- ============================================
INSERT INTO users (email, password, full_name, role, address, contact, status) VALUES
('admin@barangay.gov.ph', '$2y$10$dFLHZxMtWoAKZlFDgU.rCOWJ7Scy8GxHO.7gvLvBNDWTQGf9VvYeK', 'Barangay Administrator', 'admin', 'Barangay Hall, ACLC', '09171234567', 'active'),
('staff@barangay.gov.ph', '$2y$10$b3vgkBNRKaO5FaYNElIeeOTL.o0vPWfhR25bDpiCq5NrI6IZJcw3G', 'Juan dela Cruz',         'staff', '123 Rizal St., ACLC', '09181234567', 'active');

-- Sample complaints
INSERT INTO complaints (case_number, complainant_name, complainant_address, complainant_contact, respondent_name, respondent_address, incident_description, date_of_incident, status) VALUES
('C-001', 'Juan Dela Cruz',  '123 Rizal St., Barangay ACLC', '09171234567', 'Pedro Santos', '456 Mabini St., Barangay ACLC', 'Respondent has been playing loud music late at night causing disturbance to neighbors.', '2026-04-01', 'Pending'),
('C-002', 'Maria Santos',    '78 Luna Ave., Barangay ACLC',  '09182345678', 'Anna Lee',     '90 Burgos St., Barangay ACLC',  'Dispute over property boundary. Respondent allegedly built a fence encroaching on complainant''s land.', '2026-04-03', 'Ongoing'),
('C-003', 'Mark Reyes',      '12 Bonifacio Rd., Barangay ACLC', '09193456789', 'John Doe',  '34 Aguinaldo St., Barangay ACLC', 'Verbal altercation at the public market. Respondent used threatening language.', '2026-04-05', 'Resolved');

INSERT INTO blotter_records (complaint_id, blotter_number, hearing_date, remarks) VALUES
(1, 'B-001', '2026-04-10', 'Scheduled for hearing'),
(2, 'B-002', '2026-04-12', 'Mediation ongoing'),
(3, 'B-003', '2026-04-08', 'Case closed - settlement reached');

-- Sample messages
INSERT INTO messages (sender_name, sender_email, subject, message) VALUES
('Pedro Santos', 'pedro@email.com', 'Question about my case', 'Good day po. Gusto ko pong malaman ang status ng aking kaso. Salamat.'),
('Ana Reyes', 'ana@email.com', 'Request for barangay clearance', 'Pwede po bang magtanong kung paano mag-request ng barangay clearance online?');
