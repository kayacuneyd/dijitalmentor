-- Migration: Create Lesson Agreements and Reports Tables
-- Created: 2025-12-01
-- Description: Creates tables for lesson agreements and lesson reports

-- Lesson Agreements Table
-- Stores agreements between teachers and parents about lessons
CREATE TABLE IF NOT EXISTS lesson_agreements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    parent_id INT NOT NULL,
    conversation_id INT NULL COMMENT 'Related conversation',
    subject_id INT NOT NULL,
    lesson_date DATE NOT NULL COMMENT 'Planned lesson date',
    lesson_time TIME NULL COMMENT 'Planned lesson time',
    location ENUM('online', 'in_person', 'address') DEFAULT 'online',
    address_detail VARCHAR(255) NULL COMMENT 'Address if in_person',
    agreed_price DECIMAL(10, 2) NOT NULL COMMENT 'Agreed hourly rate',
    agreed_duration DECIMAL(4, 2) NOT NULL DEFAULT 1.00 COMMENT 'Duration in hours',
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE SET NULL,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    INDEX idx_teacher (teacher_id),
    INDEX idx_parent (parent_id),
    INDEX idx_status (status),
    INDEX idx_lesson_date (lesson_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lesson Reports Table
-- Stores post-lesson reports filled by teachers
CREATE TABLE IF NOT EXISTS lesson_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agreement_id INT NOT NULL COMMENT 'Related lesson agreement',
    teacher_id INT NOT NULL,
    parent_id INT NOT NULL,
    lesson_date DATE NOT NULL COMMENT 'Actual lesson date',
    attendance ENUM('present', 'absent', 'late') DEFAULT 'present',
    topics_covered TEXT NULL COMMENT 'Topics covered in the lesson',
    homework_assigned TEXT NULL COMMENT 'Homework assigned',
    teacher_notes TEXT NULL COMMENT 'Teacher notes about the lesson',
    student_progress TEXT NULL COMMENT 'Student progress notes',
    next_lesson_date DATE NULL COMMENT 'Next planned lesson date',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (agreement_id) REFERENCES lesson_agreements(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_agreement (agreement_id),
    INDEX idx_teacher (teacher_id),
    INDEX idx_parent (parent_id),
    INDEX idx_lesson_date (lesson_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Update teacher_reviews table to add agreement_id
ALTER TABLE teacher_reviews
ADD COLUMN IF NOT EXISTS agreement_id INT NULL COMMENT 'Related lesson agreement' AFTER reviewer_id,
ADD FOREIGN KEY IF NOT EXISTS fk_review_agreement (agreement_id) REFERENCES lesson_agreements(id) ON DELETE SET NULL,
ADD INDEX IF NOT EXISTS idx_agreement (agreement_id);

