-- Migration: Add Lesson Agreements (onay formu) table
-- Created: 2025-11-22

CREATE TABLE IF NOT EXISTS lesson_agreements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    sender_id INT NOT NULL COMMENT 'Formu gönderen',
    recipient_id INT NOT NULL COMMENT 'Formu alan',
    subject_id INT NOT NULL,
    lesson_location ENUM('student_home', 'turkish_center', 'online') NOT NULL,
    lesson_address VARCHAR(255) DEFAULT NULL COMMENT 'Fiziksel ders adresi',
    meeting_platform ENUM('jitsi') DEFAULT NULL,
    meeting_link VARCHAR(500) DEFAULT NULL,
    hourly_rate DECIMAL(10,2) NOT NULL,
    hours_per_week TINYINT DEFAULT 1,
    start_date DATE DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    status ENUM('pending', 'accepted', 'rejected', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    responded_at TIMESTAMP NULL DEFAULT NULL,

    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,

    INDEX idx_conversation (conversation_id),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
