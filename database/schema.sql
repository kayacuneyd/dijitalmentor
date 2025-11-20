-- bezmidar_db Complete Schema
-- Character set: utf8mb4 for Turkish character support

CREATE DATABASE IF NOT EXISTS bezmidar_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bezmidar_db;

-- ===================================
-- USERS TABLE
-- ===================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(20) NOT NULL UNIQUE COMMENT 'Format: +49xxxxxxxxxx',
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('student', 'parent', 'admin') NOT NULL DEFAULT 'student',
    avatar_url VARCHAR(255) DEFAULT NULL,
    email VARCHAR(100) DEFAULT NULL,
    is_verified BOOLEAN DEFAULT 0 COMMENT 'Öğrenci belgesi onayı',
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_phone (phone),
    INDEX idx_role (role),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- TEACHER PROFILES
-- ===================================
CREATE TABLE teacher_profiles (
    user_id INT PRIMARY KEY,
    university VARCHAR(100) DEFAULT NULL,
    department VARCHAR(100) DEFAULT NULL,
    graduation_year YEAR DEFAULT NULL,
    bio TEXT,
    city VARCHAR(50) DEFAULT NULL,
    zip_code VARCHAR(10) DEFAULT NULL COMMENT 'PLZ (örn: 70806)',
    address_detail VARCHAR(255) DEFAULT NULL,
    hourly_rate DECIMAL(10, 2) DEFAULT 20.00,
    video_intro_url VARCHAR(255) DEFAULT NULL COMMENT 'Tanıtım videosu',
    experience_years TINYINT DEFAULT 0,
    total_students INT DEFAULT 0 COMMENT 'Toplam öğrenci sayısı',
    rating_avg DECIMAL(3,2) DEFAULT 0.00 COMMENT '0.00 - 5.00',
    review_count INT DEFAULT 0,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_city_zip (city, zip_code),
    INDEX idx_rate (hourly_rate),
    INDEX idx_rating (rating_avg),
    FULLTEXT idx_bio (bio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- SUBJECTS
-- ===================================
CREATE TABLE subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL COMMENT 'Türkçe isim',
    name_de VARCHAR(50) DEFAULT NULL COMMENT 'Almanca isim',
    slug VARCHAR(50) NOT NULL UNIQUE,
    icon VARCHAR(50) DEFAULT NULL COMMENT 'Emoji veya icon class',
    sort_order INT DEFAULT 0,
    
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- TEACHER-SUBJECT RELATIONSHIP
-- ===================================
CREATE TABLE teacher_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    subject_id INT NOT NULL,
    proficiency_level ENUM('beginner', 'intermediate', 'advanced', 'expert') DEFAULT 'intermediate',
    
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_teacher_subject (teacher_id, subject_id),
    INDEX idx_teacher (teacher_id),
    INDEX idx_subject (subject_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- UNLOCK REQUESTS (Contact Requests)
-- ===================================
CREATE TABLE unlock_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parent_id INT NOT NULL,
    teacher_id INT NOT NULL,
    status ENUM('pending', 'approved', 'viewed') DEFAULT 'pending',
    message TEXT COMMENT 'Velinin mesajı',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    viewed_at TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_teacher_status (teacher_id, status),
    INDEX idx_parent (parent_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- REVIEWS
-- ===================================
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parent_id INT NOT NULL,
    teacher_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    is_approved BOOLEAN DEFAULT 0 COMMENT 'Admin onayı',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_teacher_approved (teacher_id, is_approved),
    INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- FAVORITES
-- ===================================
CREATE TABLE favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parent_id INT NOT NULL,
    teacher_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorite (parent_id, teacher_id),
    INDEX idx_parent (parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- LESSON REQUESTS (Veli Ders Talepleri)
-- ===================================
CREATE TABLE lesson_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parent_id INT NOT NULL,
    subject_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    city VARCHAR(100),
    budget_range VARCHAR(50),
    status ENUM('active', 'closed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES users(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- SEED DATA
-- ===================================

-- Subjects (Dersler)
INSERT INTO subjects (name, name_de, slug, icon, sort_order) VALUES
('Matematik', 'Mathematik', 'matematik', '📐', 1),
('Almanca', 'Deutsch', 'almanca', '🇩🇪', 2),
('İngilizce', 'Englisch', 'ingilizce', '🇬🇧', 3),
('Türkçe', 'Türkisch', 'turkce', '🇹🇷', 4),
('Fizik', 'Physik', 'fizik', '⚛️', 5),
('Kimya', 'Chemie', 'kimya', '🧪', 6),
('Biyoloji', 'Biologie', 'biyoloji', '🧬', 7),
('Tarih', 'Geschichte', 'tarih', '📜', 8),
('Coğrafya', 'Geographie', 'cografya', '🌍', 9),
('Müzik', 'Musik', 'muzik', '🎵', 10),
('Resim', 'Kunst', 'resim', '🎨', 11),
('Bilgisayar', 'Informatik', 'bilgisayar', '💻', 12);

-- Test admin user (password: admin123)
INSERT INTO users (phone, password_hash, full_name, role, is_verified, is_active) VALUES
('+491234567890', '$2y$10$rJYQXQxQxQxQxQxQxQxQxuQxQxQxQxQxQxQxQxQxQxQxQxQ', 'Admin User', 'admin', 1, 1);
