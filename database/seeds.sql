-- ============================================================================
-- Aengelos Tattoo Studio Management System - Seed Data
-- ============================================================================

USE aengelos_studio;

-- 1. Seed Default Studio Accounts (Default password: password123)
-- Password hash generated with PASSWORD_BCRYPT (cost 10) for 'password123'
INSERT INTO users (role, full_name, email, phone, password_hash) VALUES
('admin', 'Aengelos Studio Admin', 'admin@aengelos.com', '09171112222', '$2y$10$Wx4Socf4XIHzhKvipeBomOccOixa.NBNbm.2UWJvyr80rM9IeUbsW'),
('artist', 'Senior Tattoo Artist', 'artist@aengelos.com', '09173334444', '$2y$10$Wx4Socf4XIHzhKvipeBomOccOixa.NBNbm.2UWJvyr80rM9IeUbsW'),
('client', 'Juan Dela Cruz', 'client@aengelos.com', '09175556666', '$2y$10$Wx4Socf4XIHzhKvipeBomOccOixa.NBNbm.2UWJvyr80rM9IeUbsW');

-- 2. Seed Service Pricing Matrix
INSERT INTO service_pricing_matrix (service_name, service_category, base_price, deposit_required, est_duration_mins) VALUES
('Minimalist Flash Piece (2x2 in)', 'Flash Art', 1500.00, 500.00, 60),
('Custom Anatomical Piece (4x6 in)', 'Custom Design', 4500.00, 1000.00, 180),
('Full Scar / Old Ink Cover-Up', 'Cover-Up', 7500.00, 2000.00, 240);

-- 3. Seed Sterile Supplies & FIFO Batches
INSERT INTO sterile_supplies (supply_name, category, unit, reorder_threshold, total_stock) VALUES
('Sterile 1205RL Needle Cartridge', 'Needle Cartridge', 'pcs', 20, 40),
('Certified Black Tattoo Pigment (8oz)', 'Certified Pigment', 'bottles', 5, 8),
('Sanitary Machine Barrier Film', 'Protective Barrier', 'pcs', 25, 20);

INSERT INTO supply_batches_fifo (supply_id, batch_number, lot_number, qty_received, qty_remaining, received_date, expiration_date) VALUES
(1, 'BATCH-NDL-01', 'LOT-2026A', 20, 20, '2026-08-01', '2026-11-01'),
(1, 'BATCH-NDL-02', 'LOT-2026B', 20, 20, '2026-09-15', '2027-08-15'),
(2, 'BATCH-PIG-01', 'LOT-INK09', 8, 8, '2026-09-01', '2027-09-01'),
(3, 'BATCH-BAR-01', 'LOT-BAR77', 20, 20, '2026-07-20', '2026-10-30');
