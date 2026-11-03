-- ============================================================================
-- Aengelos Tattoo Studio Management System - Seed Data
-- ============================================================================

USE aengelos_studio;

-- 1. Seed Studio Accounts (Default password: password123)
-- Password hash generated with PASSWORD_BCRYPT (cost 10) for 'password123'
INSERT INTO users (role, full_name, email, phone, password_hash) VALUES
('admin', 'Aengelos Studio Admin', 'admin@aengelos.com', '09171112222', '$2y$10$Wx4Socf4XIHzhKvipeBomOccOixa.NBNbm.2UWJvyr80rM9IeUbsW'),
('artist', 'Ricky Sta. Ana', 'artist@aengelos.com', '09173334444', '$2y$10$Wx4Socf4XIHzhKvipeBomOccOixa.NBNbm.2UWJvyr80rM9IeUbsW'),
('artist', 'Wiji Lacsamana', 'wiji@aengelos.com', '09177778888', '$2y$10$Wx4Socf4XIHzhKvipeBomOccOixa.NBNbm.2UWJvyr80rM9IeUbsW'),
('artist', 'Myke Sambajon', 'myke@aengelos.com', '09179991111', '$2y$10$Wx4Socf4XIHzhKvipeBomOccOixa.NBNbm.2UWJvyr80rM9IeUbsW'),
('client', 'Juan Dela Cruz', 'client@aengelos.com', '09175556666', '$2y$10$Wx4Socf4XIHzhKvipeBomOccOixa.NBNbm.2UWJvyr80rM9IeUbsW');

-- 2. Seed Service Pricing Matrix
INSERT INTO service_pricing_matrix (service_name, service_category, base_price, deposit_required, est_duration_mins) VALUES
('Minimalist Flash Piece (2x2 in)', 'Flash Art', 1500.00, 500.00, 60),
('Custom Anatomical Piece (4x6 in)', 'Custom Design', 4500.00, 1000.00, 180),
('Full Scar / Old Ink Cover-Up', 'Cover-Up', 7500.00, 2000.00, 240);

-- 3. Seed Shopee-Style Real Client Inked Works & Proof of Work Catalog
INSERT INTO tattoo_catalog 
(uploader_id, title, category, description, price, deposit_required, est_duration_mins, image_path, tags, is_featured, status) 
VALUES
(2, 'Ryu Dragon Full Arm Sleeve (Client Proof)', 'Japanese / Irezumi', 'Authentic proof of work: Completed Japanese Irezumi oriental dragon winding through dark thunderclouds and crashing ocean waves. Inked on a real studio client forearm and sleeve over multiple sessions with healed deep black-and-grey contrast and crimson accents.', 7500.00, 2000.00, 240, 'uploads/artworks/client_proof_japanese_dragon.jpg', 'client proof,dragon,irezumi,japanese,sleeve,healed,sample work', 1, 'available'),
(4, 'Neo-Traditional Sovereign Dagger & Rose (Client Proof)', 'Flash Art', 'Authentic proof of work: Ornate filigree dagger thrust through a velvety crimson rose bloom with blood droplet accents. Inked on a real client outer forearm with saturated color packing and bold, crisp outlines.', 4500.00, 1200.00, 180, 'uploads/artworks/client_proof_neotrad_dagger.jpg', 'client proof,neotraditional,rose,dagger,forearm,color,sample work', 1, 'available'),
(3, 'Celestial Sol & Crescent Wrist Alignment (Client Proof)', 'Minimalist', 'Authentic proof of work: Delicate fine-line single needle sacred geometry solar compass and crescent moon cleanly inked on a female client inner wrist. Crisp micro-stippling and healed linework.', 2800.00, 800.00, 90, 'uploads/artworks/client_proof_celestial_wrist.jpg', 'client proof,minimalist,geometry,celestial,sun,moon,wrist,fineline,sample work', 1, 'available'),
(2, 'Hannya Kiku Guardian Demon Mask (Client Proof)', 'Japanese / Irezumi', 'Authentic proof of work: Traditional Japanese Hannya guardian mask with golden horns and dark shading, tattooed on a client upper shoulder and deltoid. Powerful protective amulet with bold shading.', 6800.00, 1800.00, 210, 'uploads/artworks/client_proof_oni_shoulder.jpg', 'client proof,hannya,oni,japanese,irezumi,shoulder,healed,sample work', 1, 'available'),
(3, 'Deaths-Head Hawkmoth & Lavender Halo (Client Proof)', 'Minimalist', 'Authentic proof of work: Fine-line botanical moth with skull thoracic emblem nestled inside a botanical wreath of lavender sprigs and lunar cycle phases. Healed blackwork on a real client forearm.', 3200.00, 900.00, 120, 'uploads/artworks/client_proof_botanical_moth.jpg', 'client proof,botanical,moth,lavender,moon,dotwork,forearm,fineline,sample work', 0, 'available'),
(4, 'Neuro-Core Cybernetic Biomechanical Skull (Client Proof)', 'Realism', 'Authentic proof of work: Dark cyberpunk biomechanical skull anatomy with micro-circuits and hydraulic cables, tattooed on a client bicep and upper arm. Heavy blackwork with mechanical realism.', 5500.00, 1500.00, 180, 'uploads/artworks/client_proof_cyber_skull.jpg', 'client proof,cyberpunk,biomechanical,skull,bicep,blackwork,realism,sample work', 0, 'available');
