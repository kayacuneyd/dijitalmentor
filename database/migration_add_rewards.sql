-- Migration: Add rewards and lesson hour tracking tables
-- Created: 2025-11-22

-- Ders saati takibi
CREATE TABLE IF NOT EXISTS lesson_hours_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    agreement_id INT NOT NULL,
    hours_completed DECIMAL(5,2) NOT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (agreement_id) REFERENCES lesson_agreements(id) ON DELETE CASCADE,

    INDEX idx_user (user_id),
    INDEX idx_agreement (agreement_id),
    INDEX idx_completed (completed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ödüller
CREATE TABLE IF NOT EXISTS rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reward_type ENUM('parent_5h', 'parent_10h', 'parent_15h', 'teacher_voucher') NOT NULL,
    reward_title VARCHAR(200) NOT NULL,
    reward_description TEXT,
    reward_value DECIMAL(10,2) DEFAULT 0 COMMENT 'Ödül değeri (€)',

    hours_milestone INT NOT NULL COMMENT 'Kaç saat sonra verildi',
    awarded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_claimed BOOLEAN DEFAULT 0,
    claimed_at TIMESTAMP NULL,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,

    INDEX idx_user (user_id),
    INDEX idx_type (reward_type),
    INDEX idx_claimed (is_claimed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ödül basamakları konfigürasyonu
CREATE TABLE IF NOT EXISTS reward_milestones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role ENUM('student', 'parent') NOT NULL,
    hours_required INT NOT NULL,
    reward_type VARCHAR(50) NOT NULL,
    reward_title VARCHAR(200) NOT NULL,
    reward_description TEXT,
    reward_value DECIMAL(10,2) DEFAULT 0,
    is_active BOOLEAN DEFAULT 1,

    UNIQUE KEY uniq_role_hours (role, hours_required),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Varsayılan milestone kayıtları
INSERT IGNORE INTO reward_milestones (role, hours_required, reward_type, reward_title, reward_description, reward_value) VALUES
('parent', 5, 'parent_5h', '5 Saat Ödülü', '%10 indirim kuponu', 5.00),
('parent', 10, 'parent_10h', '10 Saat Ödülü', '%15 indirim + dijital materyal', 10.00),
('parent', 15, 'parent_15h', '15+ Saat Ödülü', '%20 indirim + 1 saat ücretsiz + Premium', 20.00),
('student', 20, 'teacher_voucher', '20 Saat Hediye Çeki', '10€ Amazon', 10.00),
('student', 50, 'teacher_voucher', '50 Saat Hediye Çeki', '25€ Amazon', 25.00),
('student', 100, 'teacher_voucher', '100 Saat Hediye Çeki', '50€ Amazon', 50.00);
