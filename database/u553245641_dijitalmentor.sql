-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 22, 2025 at 04:09 PM
-- Server version: 11.8.3-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u553245641_dijitalmentor`
--

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

CREATE TABLE `conversations` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `last_message_text` text DEFAULT NULL,
  `last_message_at` timestamp NULL DEFAULT NULL,
  `teacher_unread_count` int(11) DEFAULT 0,
  `parent_unread_count` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lesson_agreements`
--

CREATE TABLE `lesson_agreements` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL COMMENT 'Formu gönderen',
  `recipient_id` int(11) NOT NULL COMMENT 'Formu alan',
  `subject_id` int(11) NOT NULL,
  `lesson_location` enum('student_home','turkish_center','online') NOT NULL,
  `lesson_address` varchar(255) DEFAULT NULL COMMENT 'Fiziksel ders adresi',
  `meeting_platform` enum('jitsi') DEFAULT NULL,
  `meeting_link` varchar(500) DEFAULT NULL,
  `hourly_rate` decimal(10,2) NOT NULL,
  `hours_per_week` tinyint(4) DEFAULT 1,
  `start_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','accepted','rejected','cancelled') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `responded_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lesson_hours_tracking`
--

CREATE TABLE `lesson_hours_tracking` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `agreement_id` int(11) NOT NULL,
  `hours_completed` decimal(5,2) NOT NULL,
  `completed_at` timestamp NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lesson_requests`
--

CREATE TABLE `lesson_requests` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `budget_range` varchar(50) DEFAULT NULL COMMENT 'Örn: 15-25€',
  `status` enum('active','matched','completed','cancelled') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lesson_requests`
--

INSERT INTO `lesson_requests` (`id`, `parent_id`, `subject_id`, `title`, `description`, `city`, `budget_range`, `status`, `created_at`, `updated_at`) VALUES
(1, 10, 2, 'A2 Almanca', 'Vatandaşlık sınavına hazırlayacak öğretmen aranıyor', 'Berlin', '30€', 'active', '2025-11-21 22:54:46', '2025-11-21 22:54:46'),
(2, 11, 1, 'Abitur Mathe', 'Hazırlık kursu', 'Kornwestheim ', '25€', 'active', '2025-11-22 12:26:06', '2025-11-22 12:26:06');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message_text` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL COMMENT '1-5',
  `comment` text DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rewards`
--

CREATE TABLE `rewards` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reward_type` enum('parent_5h','parent_10h','parent_15h','teacher_voucher') NOT NULL,
  `reward_title` varchar(200) NOT NULL,
  `reward_description` text DEFAULT NULL,
  `reward_value` decimal(10,2) DEFAULT 0.00 COMMENT 'Ödül değeri (€)',
  `hours_milestone` int(11) NOT NULL COMMENT 'Kaç saat sonra verildi',
  `awarded_at` timestamp NULL DEFAULT current_timestamp(),
  `is_claimed` tinyint(1) DEFAULT 0,
  `claimed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reward_milestones`
--

CREATE TABLE `reward_milestones` (
  `id` int(11) NOT NULL,
  `role` enum('student','parent') NOT NULL,
  `hours_required` int(11) NOT NULL,
  `reward_type` varchar(50) NOT NULL,
  `reward_title` varchar(200) NOT NULL,
  `reward_description` text DEFAULT NULL,
  `reward_value` decimal(10,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reward_milestones`
--

INSERT INTO `reward_milestones` (`id`, `role`, `hours_required`, `reward_type`, `reward_title`, `reward_description`, `reward_value`, `is_active`) VALUES
(1, 'parent', 5, 'parent_5h', '5 Saat Ödülü', '%10 indirim kuponu', 5.00, 1),
(2, 'parent', 10, 'parent_10h', '10 Saat Ödülü', '%15 indirim + dijital materyal', 10.00, 1),
(3, 'parent', 15, 'parent_15h', '15+ Saat Ödülü', '%20 indirim + 1 saat ücretsiz + Premium', 20.00, 1),
(4, 'student', 20, 'teacher_voucher', '20 Saat Hediye Çeki', '10€ Amazon', 10.00, 1),
(5, 'student', 50, 'teacher_voucher', '50 Saat Hediye Çeki', '25€ Amazon', 25.00, 1),
(6, 'student', 100, 'teacher_voucher', '100 Saat Hediye Çeki', '50€ Amazon', 50.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL COMMENT 'Türkçe isim',
  `name_de` varchar(50) DEFAULT NULL COMMENT 'Almanca isim',
  `slug` varchar(50) NOT NULL,
  `icon` varchar(10) DEFAULT NULL COMMENT 'Emoji icon',
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `name`, `name_de`, `slug`, `icon`, `sort_order`, `is_active`) VALUES
(1, 'Matematik', 'Mathematik', 'matematik', '📐', 1, 1),
(2, 'Almanca', 'Deutsch', 'almanca', '🇩🇪', 2, 1),
(3, 'İngilizce', 'Englisch', 'ingilizce', '🇬🇧', 3, 1),
(4, 'Türkçe', 'Türkisch', 'turkce', '🇹🇷', 4, 1),
(5, 'Fizik', 'Physik', 'fizik', '⚛️', 5, 1),
(6, 'Kimya', 'Chemie', 'kimya', '🧪', 6, 1),
(7, 'Biyoloji', 'Biologie', 'biyoloji', '🧬', 7, 1),
(8, 'Tarih', 'Geschichte', 'tarih', '📜', 8, 1),
(9, 'Coğrafya', 'Geographie', 'cografya', '🌍', 9, 1),
(10, 'Müzik', 'Musik', 'muzik', '🎵', 10, 1),
(11, 'Resim', 'Kunst', 'resim', '🎨', 11, 1),
(12, 'Bilgisayar', 'Informatik', 'bilgisayar', '💻', 12, 1);

-- --------------------------------------------------------

--
-- Table structure for table `teacher_profiles`
--

CREATE TABLE `teacher_profiles` (
  `user_id` int(11) NOT NULL,
  `university` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `graduation_year` year(4) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `zip_code` varchar(10) DEFAULT NULL COMMENT 'PLZ (örn: 70806)',
  `address_detail` varchar(255) DEFAULT NULL,
  `hourly_rate` decimal(10,2) DEFAULT 20.00,
  `video_intro_url` varchar(255) DEFAULT NULL COMMENT 'Tanıtım videosu',
  `cv_url` varchar(255) DEFAULT NULL COMMENT 'CV dosyası (premium)',
  `experience_years` tinyint(4) DEFAULT 0,
  `total_students` int(11) DEFAULT 0 COMMENT 'Toplam öğrenci sayısı',
  `rating_avg` decimal(3,2) DEFAULT 0.00 COMMENT '0.00 - 5.00',
  `review_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teacher_profiles`
--

INSERT INTO `teacher_profiles` (`user_id`, `university`, `department`, `graduation_year`, `bio`, `city`, `zip_code`, `address_detail`, `hourly_rate`, `video_intro_url`, `cv_url`, `experience_years`, `total_students`, `rating_avg`, `review_count`) VALUES
(1, 'TU Berlin', 'Bilgisayar Mühendisliği', '2020', 'Bilgisayar mühendisliği mezunuyum. Matematik ve Fizik konularında 5 yıllık öğretmenlik deneyimim var.', 'Berlin', '10115', NULL, 25.00, NULL, NULL, 5, 0, 4.80, 12),
(2, 'LMU München', 'Alman Dili ve Edebiyatı', '2019', 'Almanca ve İngilizce öğretmeniyim. İletişim odaklı öğretim yöntemleri kullanıyorum.', 'München', '80331', NULL, 20.00, NULL, NULL, 4, 0, 5.00, 5),
(3, 'RWTH Aachen', 'Makine Mühendisliği', '2021', 'Matematik, Kimya ve Biyoloji derslerinde uzmanım.', 'Aachen', '52062', NULL, 18.00, NULL, NULL, 2, 0, 4.50, 3),
(4, 'Universität Hamburg', 'Psikoloji', '2018', 'İngilizce ve Türkçe öğretmeniyim. Öğrenci merkezli yaklaşım benimsiyorum.', 'Hamburg', '20095', NULL, 22.00, NULL, NULL, 6, 0, 4.90, 8),
(5, 'Goethe Universität', 'Ekonomi', '2022', 'Matematik ve ekonomi derslerinde yardımcı oluyorum.', 'Frankfurt', '60311', NULL, 20.00, NULL, NULL, 1, 0, 0.00, 0),
(8, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 20.00, NULL, NULL, 0, 0, 0.00, 0),
(9, NULL, NULL, NULL, '', 'Kornwestheim', '70806', NULL, 20.00, NULL, NULL, 0, 0, 0.00, 0);

-- --------------------------------------------------------

--
-- Table structure for table `teacher_subjects`
--

CREATE TABLE `teacher_subjects` (
  `teacher_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `proficiency_level` enum('beginner','intermediate','advanced','expert') DEFAULT 'intermediate'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teacher_subjects`
--

INSERT INTO `teacher_subjects` (`teacher_id`, `subject_id`, `proficiency_level`) VALUES
(1, 1, 'expert'),
(1, 5, 'advanced'),
(2, 2, 'expert'),
(2, 3, 'advanced'),
(3, 1, 'intermediate'),
(3, 6, 'advanced'),
(3, 7, 'intermediate'),
(4, 3, 'expert'),
(4, 4, 'expert'),
(5, 1, 'advanced');

-- --------------------------------------------------------

--
-- Table structure for table `unlock_requests`
--

CREATE TABLE `unlock_requests` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `message` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `phone` varchar(20) NOT NULL COMMENT 'Format: +49xxxxxxxxxx',
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('student','parent','admin') NOT NULL DEFAULT 'student',
  `avatar_url` varchar(255) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `zip_code` varchar(10) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0 COMMENT 'Öğrenci belgesi onayı',
  `approval_status` enum('pending','approved','rejected') DEFAULT 'approved' COMMENT 'Hesap onay durumu',
  `is_premium` tinyint(1) DEFAULT 0 COMMENT 'Premium üyelik durumu',
  `premium_expires_at` timestamp NULL DEFAULT NULL COMMENT 'Premium üyelik bitiş tarihi',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `phone`, `password_hash`, `full_name`, `role`, `avatar_url`, `email`, `city`, `zip_code`, `is_verified`, `approval_status`, `is_premium`, `premium_expires_at`, `is_active`, `created_at`, `updated_at`) VALUES
(1, '+491234567801', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ahmet Yılmaz', 'student', NULL, NULL, 'Berlin', '10115', 1, 'approved', 0, NULL, 1, '2025-11-20 23:57:07', '2025-11-20 23:57:07'),
(2, '+491234567802', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ayşe Demir', 'student', NULL, NULL, 'München', '80331', 1, 'approved', 0, NULL, 1, '2025-11-20 23:57:07', '2025-11-20 23:57:07'),
(3, '+491234567803', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mehmet Kaya', 'student', NULL, NULL, 'Aachen', '52062', 0, 'pending', 0, NULL, 1, '2025-11-20 23:57:07', '2025-11-20 23:57:07'),
(4, '+491234567804', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Zeynep Çelik', 'student', NULL, NULL, 'Hamburg', '20095', 1, 'approved', 0, NULL, 1, '2025-11-20 23:57:07', '2025-11-20 23:57:07'),
(5, '+491234567805', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Can Yıldız', 'student', NULL, NULL, 'Frankfurt', '60311', 0, 'pending', 0, NULL, 1, '2025-11-20 23:57:07', '2025-11-20 23:57:07'),
(6, '+491234567901', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Demo Veli', 'parent', NULL, NULL, NULL, NULL, 0, 'approved', 0, NULL, 1, '2025-11-20 23:57:07', '2025-11-20 23:57:07'),
(7, '+491234567902', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Test Parent', 'parent', NULL, NULL, NULL, NULL, 0, 'approved', 0, NULL, 1, '2025-11-20 23:57:07', '2025-11-20 23:57:07'),
(8, '49033393092002', '$2y$10$VQ.tKnRYb09kPPHFttmBjOzy3FTfvx/ItkX42eYQ8.lSsTlpTlIDG', 'Gerçek TEst Öğretmen', 'student', NULL, NULL, NULL, NULL, 0, 'approved', 0, NULL, 1, '2025-11-20 23:59:10', '2025-11-20 23:59:10'),
(9, '+4915205775326', '$2y$10$g/8LA5eyCDjGuGsVxQS4WuD5n6ufunkaok0gldcPAKcP.zDisIhF.', 'Cüneyt Kaya', 'student', NULL, NULL, 'Kornwestheim', '70806', 0, 'approved', 0, NULL, 1, '2025-11-21 22:05:22', '2025-11-21 22:52:05'),
(10, '+491520785263', '$2y$10$bsgpbzqhwRC/XwHGMhIfUulSXLYafx9573UxEieMaI4DoeJ4i6r5G', 'Örnek Bir Veli', 'parent', NULL, NULL, 'Berlin', '10115', 1, 'approved', 0, NULL, 1, '2025-11-21 22:53:47', '2025-11-21 22:53:47'),
(11, '4917641691456', '$2y$10$izXV6A7nDA2fWpHtpOYnuuW.kMD8JDPq3Ou/GsSw8eTzoWn3B2gkO', 'Melike Nil YILDIZ', 'parent', NULL, NULL, 'Kornwestheim', '70806', 1, 'approved', 0, NULL, 1, '2025-11-22 12:24:10', '2025-11-22 12:24:10');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_conversation` (`teacher_id`,`parent_id`),
  ADD KEY `idx_teacher` (`teacher_id`),
  ADD KEY `idx_parent` (`parent_id`),
  ADD KEY `idx_updated` (`updated_at`);

--
-- Indexes for table `lesson_agreements`
--
ALTER TABLE `lesson_agreements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `recipient_id` (`recipient_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `idx_conversation` (`conversation_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `lesson_hours_tracking`
--
ALTER TABLE `lesson_hours_tracking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_agreement` (`agreement_id`),
  ADD KEY `idx_completed` (`completed_at`);

--
-- Indexes for table `lesson_requests`
--
ALTER TABLE `lesson_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_conversation` (`conversation_id`),
  ADD KEY `idx_sender` (`sender_id`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_unread` (`conversation_id`,`is_read`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `idx_teacher` (`teacher_id`),
  ADD KEY `idx_approved` (`is_approved`);

--
-- Indexes for table `rewards`
--
ALTER TABLE `rewards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_type` (`reward_type`),
  ADD KEY `idx_claimed` (`is_claimed`);

--
-- Indexes for table `reward_milestones`
--
ALTER TABLE `reward_milestones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_role_hours` (`role`,`hours_required`),
  ADD KEY `idx_role` (`role`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `teacher_profiles`
--
ALTER TABLE `teacher_profiles`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `idx_city_zip` (`city`,`zip_code`),
  ADD KEY `idx_rate` (`hourly_rate`),
  ADD KEY `idx_rating` (`rating_avg`);
ALTER TABLE `teacher_profiles` ADD FULLTEXT KEY `idx_bio` (`bio`);

--
-- Indexes for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD PRIMARY KEY (`teacher_id`,`subject_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `unlock_requests`
--
ALTER TABLE `unlock_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_unlock_pair` (`parent_id`,`teacher_id`),
  ADD KEY `fk_unlock_teacher` (`teacher_id`),
  ADD KEY `idx_unlock_status` (`status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD KEY `idx_phone` (`phone`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_approval` (`approval_status`),
  ADD KEY `idx_premium` (`is_premium`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lesson_agreements`
--
ALTER TABLE `lesson_agreements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lesson_hours_tracking`
--
ALTER TABLE `lesson_hours_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lesson_requests`
--
ALTER TABLE `lesson_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rewards`
--
ALTER TABLE `rewards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reward_milestones`
--
ALTER TABLE `reward_milestones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `unlock_requests`
--
ALTER TABLE `unlock_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conversations_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lesson_agreements`
--
ALTER TABLE `lesson_agreements`
  ADD CONSTRAINT `lesson_agreements_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lesson_agreements_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lesson_agreements_ibfk_3` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lesson_agreements_ibfk_4` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lesson_hours_tracking`
--
ALTER TABLE `lesson_hours_tracking`
  ADD CONSTRAINT `lesson_hours_tracking_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lesson_hours_tracking_ibfk_2` FOREIGN KEY (`agreement_id`) REFERENCES `lesson_agreements` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lesson_requests`
--
ALTER TABLE `lesson_requests`
  ADD CONSTRAINT `lesson_requests_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lesson_requests_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rewards`
--
ALTER TABLE `rewards`
  ADD CONSTRAINT `rewards_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_profiles`
--
ALTER TABLE `teacher_profiles`
  ADD CONSTRAINT `teacher_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD CONSTRAINT `teacher_subjects_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `unlock_requests`
--
ALTER TABLE `unlock_requests`
  ADD CONSTRAINT `fk_unlock_parent` FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_unlock_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
