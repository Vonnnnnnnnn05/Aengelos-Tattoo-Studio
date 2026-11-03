-- ============================================================================
-- Aengelos Tattoo Studio Management System - MySQL Database Schema
-- Character Set: utf8mb4, Collation: utf8mb4_unicode_ci
-- Shopee-Style E-Commerce Flash Catalog & Studio Booking Management
-- ============================================================================

CREATE DATABASE IF NOT EXISTS aengelos_studio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE aengelos_studio;

-- Drop obsolete inventory tables if present
DROP TABLE IF EXISTS supply_consumption_logs;
DROP TABLE IF EXISTS supply_batches_fifo;
DROP TABLE IF EXISTS sterile_supplies;

-- Drop operational tables in reverse dependency order
DROP TABLE IF EXISTS session_logs;
DROP TABLE IF EXISTS digital_waivers;
DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS visualizer_mockups;
DROP TABLE IF EXISTS tattoo_catalog;
DROP TABLE IF EXISTS service_pricing_matrix;
DROP TABLE IF EXISTS users;

-- 1. Users & Client Profiles Directory (RBAC)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role ENUM('client', 'artist', 'admin') NOT NULL DEFAULT 'client',
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    phone VARCHAR(30) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Service Pricing Matrix (Baseline Studio Service Tiers)
CREATE TABLE service_pricing_matrix (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(80) NOT NULL,
    service_category ENUM('Custom Design', 'Flash Art', 'Cover-Up') NOT NULL,
    base_price DECIMAL(10,2) NOT NULL,
    deposit_required DECIMAL(10,2) NOT NULL,
    est_duration_mins INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Shopee-Style Tattoo Design & Flash Art Catalog
CREATE TABLE tattoo_catalog (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uploader_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    category VARCHAR(80) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    deposit_required DECIMAL(10,2) NOT NULL,
    est_duration_mins INT NOT NULL DEFAULT 120,
    image_path VARCHAR(255) NOT NULL,
    tags VARCHAR(255) DEFAULT '',
    is_featured TINYINT(1) DEFAULT 0,
    status ENUM('available', 'reserved', 'sold_out', 'archived') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploader_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. 2D Canvas Visualizer Mockups
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

-- 5. Online Appointments & Availments (Deposit Verification)
CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    artist_id INT NOT NULL,
    service_id INT NULL,
    catalog_id INT NULL,
    mockup_id INT NULL,
    body_region VARCHAR(80) NOT NULL DEFAULT 'Forearm',
    placement_notes VARCHAR(255) NULL,
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
    FOREIGN KEY (service_id) REFERENCES service_pricing_matrix(id) ON DELETE SET NULL,
    FOREIGN KEY (catalog_id) REFERENCES tattoo_catalog(id) ON DELETE SET NULL,
    FOREIGN KEY (mockup_id) REFERENCES visualizer_mockups(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Digital Consent Waiver Archive
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

-- 7. Artist Session Execution Logs
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
