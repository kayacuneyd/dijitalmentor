-- Clear existing data (optional, be careful in production)
-- SET FOREIGN_KEY_CHECKS = 0;
-- TRUNCATE TABLE reviews;
-- TRUNCATE TABLE unlock_requests;
-- TRUNCATE TABLE teacher_subjects;
-- TRUNCATE TABLE teacher_profiles;
-- TRUNCATE TABLE users;
-- SET FOREIGN_KEY_CHECKS = 1;

-- 1. Users (Teachers)
INSERT INTO users (id, phone, password_hash, full_name, role, is_verified, is_active, avatar_url) VALUES
(101, '+4915111111111', '$2y$10$rJYQXQxQxQxQxQxQxQxQxuQxQxQxQxQxQxQxQxQxQxQxQxQ', 'Ahmet Yılmaz', 'student', 1, 1, 'https://randomuser.me/api/portraits/men/32.jpg'),
(102, '+4915122222222', '$2y$10$rJYQXQxQxQxQxQxQxQxQxuQxQxQxQxQxQxQxQxQxQxQxQxQ', 'Ayşe Demir', 'student', 1, 1, 'https://randomuser.me/api/portraits/women/44.jpg'),
(103, '+4915133333333', '$2y$10$rJYQXQxQxQxQxQxQxQxQxuQxQxQxQxQxQxQxQxQxQxQxQxQ', 'Mehmet Kaya', 'student', 1, 1, 'https://randomuser.me/api/portraits/men/85.jpg'),
(104, '+4915144444444', '$2y$10$rJYQXQxQxQxQxQxQxQxQxuQxQxQxQxQxQxQxQxQxQxQxQxQ', 'Zeynep Çelik', 'student', 1, 1, 'https://randomuser.me/api/portraits/women/68.jpg'),
(105, '+4915155555555', '$2y$10$rJYQXQxQxQxQxQxQxQxQxuQxQxQxQxQxQxQxQxQxQxQxQxQ', 'Can Yıldız', 'student', 0, 1, 'https://randomuser.me/api/portraits/men/12.jpg');

-- 2. Users (Parents)
INSERT INTO users (id, phone, password_hash, full_name, role, is_active) VALUES
(201, '+4916111111111', '$2y$10$rJYQXQxQxQxQxQxQxQxQxuQxQxQxQxQxQxQxQxQxQxQxQxQ', 'Fatma Öztürk', 'parent', 1),
(202, '+4916122222222', '$2y$10$rJYQXQxQxQxQxQxQxQxQxuQxQxQxQxQxQxQxQxQxQxQxQxQ', 'Mustafa Arslan', 'parent', 1);

-- 3. Teacher Profiles
INSERT INTO teacher_profiles (user_id, university, department, graduation_year, bio, city, zip_code, hourly_rate, experience_years, rating_avg, review_count) VALUES
(101, 'TU Berlin', 'Bilgisayar Mühendisliği', 2024, 'Merhaba! Ben Ahmet. Berlin Teknik Üniversitesi\'nde son sınıf öğrencisiyim. Matematik ve Fizik derslerinde yardımcı olabilirim. Lise yıllarımda matematik olimpiyatlarına katıldım.', 'Berlin', '10115', 25.00, 3, 4.8, 12),
(102, 'LMU München', 'Alman Dili ve Edebiyatı', 2023, 'Almanca öğrenmek isteyenlere yardımcı oluyorum. Hem gramer hem de konuşma pratiği yapabiliriz. Çocuklarla iletişimim kuvvetlidir.', 'München', '80331', 20.00, 2, 5.0, 5),
(103, 'RWTH Aachen', 'Makine Mühendisliği', 2025, 'Sayısal derslerde zorlanan öğrencilere pratik yöntemlerle ders anlatıyorum. Kimya ve Biyoloji özel ilgi alanım.', 'Aachen', '52062', 18.00, 1, 4.5, 3),
(104, 'Universität Hamburg', 'Psikoloji', 2024, 'Öğrencilerin sadece derslerine değil, motivasyonlarına da katkı sağlamayı hedefliyorum. İngilizce ve Türkçe dersleri veriyorum.', 'Hamburg', '20095', 22.00, 4, 4.9, 8),
(105, 'Goethe Universität Frankfurt', 'Hukuk', 2026, 'Tarih ve Coğrafya derslerinde yardımcı olabilirim. Henüz yeni başladım ama hevesliyim.', 'Frankfurt', '60311', 15.00, 0, 0.0, 0);

-- 4. Teacher Subjects
-- Ahmet (101): Mat (1), Fizik (5)
INSERT INTO teacher_subjects (teacher_id, subject_id, proficiency_level) VALUES
(101, 1, 'expert'),
(101, 5, 'advanced');

-- Ayşe (102): Almanca (2), İngilizce (3)
INSERT INTO teacher_subjects (teacher_id, subject_id, proficiency_level) VALUES
(102, 2, 'expert'),
(102, 3, 'advanced');

-- Mehmet (103): Kimya (6), Biyoloji (7), Mat (1)
INSERT INTO teacher_subjects (teacher_id, subject_id, proficiency_level) VALUES
(103, 6, 'intermediate'),
(103, 7, 'intermediate'),
(103, 1, 'advanced');

-- Zeynep (104): İngilizce (3), Türkçe (4)
INSERT INTO teacher_subjects (teacher_id, subject_id, proficiency_level) VALUES
(104, 3, 'expert'),
(104, 4, 'native');

-- Can (105): Tarih (8), Coğrafya (9)
INSERT INTO teacher_subjects (teacher_id, subject_id, proficiency_level) VALUES
(105, 8, 'intermediate'),
(105, 9, 'intermediate');

-- 5. Reviews
INSERT INTO reviews (parent_id, teacher_id, rating, comment, is_approved, created_at) VALUES
(201, 101, 5, 'Ahmet hoca çok ilgili, oğlumun matematik notları yükseldi.', 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(202, 101, 4, 'Ders anlatımı güzel ama bazen hızlı gidiyor.', 1, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(201, 102, 5, 'Kızım Ayşe ablasını çok seviyor, Almancası gelişti.', 1, DATE_SUB(NOW(), INTERVAL 1 WEEK)),
(202, 104, 5, 'Zeynep hanım çok kibar ve sabırlı.', 1, DATE_SUB(NOW(), INTERVAL 3 DAY));
