-- ============================================================================
-- Aengelos Tattoo Studio Management System - MySQL 8.0 Database Schema
-- Character Set: utf8mb4, Collation: utf8mb4_unicode_ci
-- ============================================================================

CREATE DATABASE IF NOT EXISTS aengelos_studio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE aengelos_studio;

-- 1. Users & Client Profiles Directory (RBAC)
DROP TABLE IF EXISTS supply_consumption_logs;
DROP TABLE IF EXISTS supply_batches_fifo;
DROP TABLE IF EXISTS sterile_supplies;
DROP TABLE IF EXISTS session_logs;
DROP TABLE IF EXISTS digital_waivers;
DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS visualizer_mockups;
DROP TABLE IF EXISTS service_pricing_matrix;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role ENUM('client', 'artist', 'admin') NOT NULL DEFAULT 'client',
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    phone VARCHAR(30) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Service Pricing Matrix (Custom, Flash Art, Cover-Up)
CREATE TABLE service_pricing_matrix (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(80) NOT NULL,
    service_category ENUM('Custom Design', 'Flash Art', 'Cover-Up') NOT NULL,
    base_price DECIMAL(10,2) NOT NULL,
    deposit_required DECIMAL(10,2) NOT NULL,
    est_duration_mins INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. 2D Canvas Visualizer Mockups
CREATE TABLE visualizer_mockups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    body_region ENUM('forearm', 'calf', 'shoulder', 'back', 'chest') NOT NULL,
    raw_artwork_path VARCHAR(255) NOT NULL,
    mockup_image_path VARCHAR(255) NOT NULL,
    canvas_coords_json LONGTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Online Appointments & Manual Deposit Verification
CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    artist_id INT NOT NULL,
    service_id INT NOT NULL,
    mockup_id INT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    deposit_amount DECIMAL(10,2) NOT NULL,
    payment_reference VARCHAR(100) NOT NULL,
    receipt_image_path VARCHAR(255) NOT NULL,
    deposit_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    booking_status ENUM('pending', 'confirmed', 'rescheduled', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id),
    FOREIGN KEY (artist_id) REFERENCES users(id),
    FOREIGN KEY (service_id) REFERENCES service_pricing_matrix(id),
    FOREIGN KEY (mockup_id) REFERENCES visualizer_mockups(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Digital Consent Waiver Archive
CREATE TABLE digital_waivers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL UNIQUE,
    client_id INT NOT NULL,
    skin_allergies VARCHAR(255) DEFAULT 'None',
    keloid_history TINYINT(1) DEFAULT 0,
    medical_conditions VARCHAR(255) DEFAULT 'None',
    signature_image_path VARCHAR(255) NOT NULL,
    pdf_archive_path VARCHAR(255) NOT NULL,
    sha256_hash CHAR(64) NULL,
    signed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Artist Session Execution Logs
CREATE TABLE session_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL UNIQUE,
    artist_id INT NOT NULL,
    duration_minutes INT NOT NULL,
    total_fee DECIMAL(10,2) NOT NULL,
    session_notes TEXT,
    logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id),
    FOREIGN KEY (artist_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Sterile Supply Inventory Master
CREATE TABLE sterile_supplies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supply_name VARCHAR(120) NOT NULL,
    category ENUM('Needle Cartridge', 'Certified Pigment', 'Protective Barrier') NOT NULL,
    unit VARCHAR(30) NOT NULL DEFAULT 'pcs',
    reorder_threshold INT NOT NULL DEFAULT 15,
    total_stock INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Batch / Lot Registry & Expiration Records (FIFO)
CREATE TABLE supply_batches_fifo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supply_id INT NOT NULL,
    batch_number VARCHAR(80) NOT NULL,
    lot_number VARCHAR(80) NOT NULL,
    qty_received INT NOT NULL,
    qty_remaining INT NOT NULL,
    received_date DATE NOT NULL,
    expiration_date DATE NOT NULL,
    batch_status ENUM('active', 'depleted', 'expired') DEFAULT 'active',
    FOREIGN KEY (supply_id) REFERENCES sterile_supplies(id) ON DELETE CASCADE,
    INDEX idx_fifo_lookup (supply_id, batch_status, expiration_date, received_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Sterile Supply Consumption Audit Logs
CREATE TABLE supply_consumption_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    supply_id INT NOT NULL,
    batch_id INT NOT NULL,
    qty_used INT NOT NULL,
    deducted_by INT NOT NULL,
    deducted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES session_logs(id),
    FOREIGN KEY (supply_id) REFERENCES sterile_supplies(id),
    FOREIGN KEY (batch_id) REFERENCES supply_batches_fifo(id),
    FOREIGN KEY (deducted_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
