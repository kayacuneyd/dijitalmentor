-- Öğretmen Seed Script - 25 öğretmen için
-- Kullanım: phpMyAdmin veya MySQL'de bu dosyayı import edin
-- Mevcut 5 öğretmen varsa, 20 yeni öğretmen ekleyecek

-- Önce mevcut öğretmen sayısını kontrol edin:
-- SELECT COUNT(*) FROM users WHERE role = 'student';

-- 20 yeni öğretmen ekle (user_id 6'dan başlayarak)

-- Users tablosuna ekle
INSERT INTO users (phone, password_hash, full_name, role, city, zip_code, approval_status, is_premium, is_verified, is_active, avatar_url) VALUES
('+491500000001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Elif Özkan', 'student', 'Köln', '50667', 'approved', 0, 1, 1, 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=400&h=400&fit=crop&crop=faces'),
('+491500000002', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Burak Şahin', 'student', 'Stuttgart', '70173', 'approved', 0, 1, 1, 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&h=400&fit=crop&crop=faces'),
('+491500000003', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Selin Aydın', 'student', 'Düsseldorf', '40213', 'approved', 0, 1, 1, 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=400&h=400&fit=crop&crop=faces'),
('+491500000004', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Emre Kılıç', 'student', 'Dortmund', '44135', 'approved', 0, 1, 1, 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=400&h=400&fit=crop&crop=faces'),
('+491500000005', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Zeynep Arslan', 'student', 'Essen', '45127', 'approved', 0, 1, 1, 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=400&h=400&fit=crop&crop=faces'),
('+491500000006', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Can Yıldırım', 'student', 'Leipzig', '04109', 'approved', 0, 1, 1, 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=400&h=400&fit=crop&crop=faces'),
('+491500000007', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ayşe Çelik', 'student', 'Berlin', '10115', 'approved', 0, 1, 1, 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&h=400&fit=crop&crop=faces'),
('+491500000008', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mehmet Doğan', 'student', 'München', '80331', 'approved', 0, 1, 1, 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=400&h=400&fit=crop&crop=faces'),
('+491500000009', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Deniz Yılmaz', 'student', 'Aachen', '52062', 'approved', 0, 1, 1, 'https://images.unsplash.com/photo-1531746020798-e6953c6e8e04?w=400&h=400&fit=crop&crop=faces'),
('+491500000010', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Okan Demir', 'student', 'Hamburg', '20095', 'approved', 0, 1, 1, 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=400&h=400&fit=crop&crop=faces'),
('+491500000011', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Gizem Öztürk', 'student', 'Frankfurt', '60311', 'approved', 0, 1, 1, 'https://images.unsplash.com/photo-1488426862026-3ee34a7d66df?w=400&h=400&fit=crop&crop=faces'),
('+491500000012', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Tolga Aslan', 'student', 'Berlin', '10115', 'approved', 0, 1, 1, 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=400&h=400&fit=crop&crop=faces'),
('+491500000013', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Burcu Kaya', 'student', 'Köln', '50667', 'approved', 0, 1, 1, 'https://images.unsplash.com/photo-1531123897727-8f129e1688ce?w=400&h=400&fit=crop&crop=faces'),
('+491500000014', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kerem Özdemir', 'student', 'Stuttgart', '70173', 'approved', 0, 1, 1, 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&h=400&fit=crop&crop=faces'),
('+491500000015', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Seda Yücel', 'student', 'Düsseldorf', '40213', 'approved', 0, 1, 1, 'https://images.unsplash.com/photo-1487412720507-e7ab37603c6f?w=400&h=400&fit=crop&crop=faces'),
('+491500000016', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Onur Çetin', 'student', 'Dortmund', '44135', 'approved', 0, 1, 1, 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=400&h=400&fit=crop&crop=faces'),
('+491500000017', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ebru Şimşek', 'student', 'Essen', '45127', 'approved', 0, 1, 1, 'https://images.unsplash.com/photo-1508214751196-bcfd4ca60f91?w=400&h=400&fit=crop&crop=faces'),
('+491500000018', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Arda Güven', 'student', 'Leipzig', '04109', 'approved', 0, 1, 1, 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&h=400&fit=crop&crop=faces'),
('+491500000019', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Melis Aktaş', 'student', 'Berlin', '10115', 'approved', 0, 1, 1, 'https://images.unsplash.com/photo-1488426862026-3ee34a7d66df?w=400&h=400&fit=crop&crop=faces'),
('+491500000020', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Berk Aydın', 'student', 'München', '80331', 'approved', 0, 1, 1, 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=400&h=400&fit=crop&crop=faces');

-- Teacher Profiles ekle (user_id'ler otomatik artacak, son eklenen ID'yi kullan)
-- Önce son eklenen user_id'yi bulalım, sonra ona göre ekleyelim
-- Burada user_id'leri manuel olarak 6-25 arası varsayıyoruz

INSERT INTO teacher_profiles (user_id, university, department, graduation_year, bio, city, zip_code, hourly_rate, rating_avg, review_count, experience_years) VALUES
((SELECT id FROM users WHERE phone = '+491500000001'), 'Universität Köln', 'Matematik', 2020, 'Matematik alanında uzmanım. Öğrencilerime sabırla ve anlaşılır bir şekilde ders anlatıyorum.', 'Köln', '50667', 22.00, 4.70, 9, 3),
((SELECT id FROM users WHERE phone = '+491500000002'), 'Universität Stuttgart', 'Bilgisayar Mühendisliği', 2019, 'Bilgisayar bilimleri ve programlama konularında deneyimliyim. Pratik örneklerle öğretiyorum.', 'Stuttgart', '70173', 26.00, 4.85, 11, 4),
((SELECT id FROM users WHERE phone = '+491500000003'), 'Heinrich-Heine-Universität Düsseldorf', 'Alman Dili ve Edebiyatı', 2018, 'Almanca öğretmeniyim. İletişim odaklı ve eğlenceli dersler veriyorum.', 'Düsseldorf', '40213', 21.00, 4.90, 14, 5),
((SELECT id FROM users WHERE phone = '+491500000004'), 'TU Dortmund', 'Makine Mühendisliği', 2021, 'Fizik ve matematik derslerinde yardımcı oluyorum. Mühendislik perspektifiyle öğretiyorum.', 'Dortmund', '44135', 24.00, 4.60, 7, 2),
((SELECT id FROM users WHERE phone = '+491500000005'), 'Universität Duisburg-Essen', 'Kimya', 2019, 'Kimya ve biyoloji derslerinde uzmanım. Deneylerle öğrenmeyi destekliyorum.', 'Essen', '45127', 23.00, 4.75, 10, 4),
((SELECT id FROM users WHERE phone = '+491500000006'), 'Universität Leipzig', 'İngiliz Dili ve Edebiyatı', 2020, 'İngilizce öğretmeniyim. Konuşma pratiği ve gramer konularında destek sağlıyorum.', 'Leipzig', '04109', 20.00, 4.65, 8, 3),
((SELECT id FROM users WHERE phone = '+491500000007'), 'TU Berlin', 'Fizik', 2017, 'Fizik derslerinde uzmanım. Günlük hayattan örneklerle konuları açıklıyorum.', 'Berlin', '10115', 25.00, 4.95, 15, 6),
((SELECT id FROM users WHERE phone = '+491500000008'), 'LMU München', 'Tarih', 2021, 'Tarih ve coğrafya derslerinde yardımcı oluyorum. Hikayelerle öğrenmeyi kolaylaştırıyorum.', 'München', '80331', 19.00, 4.40, 5, 2),
((SELECT id FROM users WHERE phone = '+491500000009'), 'RWTH Aachen', 'Biyoloji', 2020, 'Biyoloji ve kimya derslerinde uzmanım. Görsel materyallerle destekliyorum.', 'Aachen', '52062', 22.00, 4.55, 6, 3),
((SELECT id FROM users WHERE phone = '+491500000010'), 'Universität Hamburg', 'Müzik', 2022, 'Müzik teorisi ve pratik derslerinde yardımcı oluyorum. Enstrüman çalmayı öğretiyorum.', 'Hamburg', '20095', 18.00, 4.20, 3, 1),
((SELECT id FROM users WHERE phone = '+491500000011'), 'Goethe Universität', 'Resim', 2021, 'Resim ve sanat derslerinde uzmanım. Yaratıcılığı destekleyen bir öğretim yöntemi kullanıyorum.', 'Frankfurt', '60311', 20.00, 4.30, 4, 2),
((SELECT id FROM users WHERE phone = '+491500000012'), 'TU Berlin', 'Elektrik Mühendisliği', 2018, 'Matematik ve fizik derslerinde yardımcı oluyorum. Mühendislik problemlerini çözme konusunda deneyimliyim.', 'Berlin', '10115', 27.00, 4.80, 12, 5),
((SELECT id FROM users WHERE phone = '+491500000013'), 'Universität Köln', 'Psikoloji', 2019, 'Almanca ve Türkçe derslerinde uzmanım. Öğrenci merkezli bir yaklaşım benimsiyorum.', 'Köln', '50667', 21.00, 4.70, 9, 4),
((SELECT id FROM users WHERE phone = '+491500000014'), 'Universität Stuttgart', 'Matematik', 2020, 'Matematik derslerinde uzmanım. Zor konuları basit ve anlaşılır şekilde anlatıyorum.', 'Stuttgart', '70173', 23.00, 4.50, 7, 3),
((SELECT id FROM users WHERE phone = '+491500000015'), 'Heinrich-Heine-Universität Düsseldorf', 'İngiliz Dili ve Edebiyatı', 2019, 'İngilizce öğretmeniyim. İletişim becerilerini geliştirmeye odaklanıyorum.', 'Düsseldorf', '40213', 22.00, 4.65, 8, 4),
((SELECT id FROM users WHERE phone = '+491500000016'), 'TU Dortmund', 'Kimya Mühendisliği', 2020, 'Kimya ve matematik derslerinde yardımcı oluyorum. Pratik uygulamalarla öğretiyorum.', 'Dortmund', '44135', 24.00, 4.60, 6, 3),
((SELECT id FROM users WHERE phone = '+491500000017'), 'Universität Duisburg-Essen', 'Biyoloji', 2021, 'Biyoloji derslerinde uzmanım. Görsel öğrenmeyi destekleyen materyaller kullanıyorum.', 'Essen', '45127', 21.00, 4.35, 4, 2),
((SELECT id FROM users WHERE phone = '+491500000018'), 'Universität Leipzig', 'Coğrafya', 2021, 'Coğrafya ve tarih derslerinde yardımcı oluyorum. Haritalar ve görsellerle öğretiyorum.', 'Leipzig', '04109', 19.00, 4.25, 3, 2),
((SELECT id FROM users WHERE phone = '+491500000019'), 'TU Berlin', 'Bilgisayar Mühendisliği', 2017, 'Bilgisayar bilimleri ve programlama derslerinde uzmanım. Proje bazlı öğretim yapıyorum.', 'Berlin', '10115', 28.00, 4.88, 13, 6),
((SELECT id FROM users WHERE phone = '+491500000020'), 'LMU München', 'Alman Dili ve Edebiyatı', 2020, 'Almanca öğretmeniyim. Dil öğrenmeyi eğlenceli ve etkili hale getiriyorum.', 'München', '80331', 20.00, 4.55, 7, 3);

-- Teacher Subjects ekle
-- Subject ID'leri: 1=Matematik, 2=Almanca, 3=İngilizce, 4=Türkçe, 5=Fizik, 6=Kimya, 7=Biyoloji, 8=Tarih, 9=Coğrafya, 10=Müzik, 11=Resim, 12=Bilgisayar

INSERT INTO teacher_subjects (teacher_id, subject_id, proficiency_level) VALUES
((SELECT id FROM users WHERE phone = '+491500000001'), 1, 'expert'),    -- Elif - Matematik
((SELECT id FROM users WHERE phone = '+491500000001'), 5, 'advanced'),  -- Elif - Fizik
((SELECT id FROM users WHERE phone = '+491500000002'), 12, 'expert'),   -- Burak - Bilgisayar
((SELECT id FROM users WHERE phone = '+491500000002'), 1, 'advanced'), -- Burak - Matematik
((SELECT id FROM users WHERE phone = '+491500000003'), 2, 'expert'),    -- Selin - Almanca
((SELECT id FROM users WHERE phone = '+491500000003'), 3, 'advanced'), -- Selin - İngilizce
((SELECT id FROM users WHERE phone = '+491500000004'), 5, 'advanced'), -- Emre - Fizik
((SELECT id FROM users WHERE phone = '+491500000004'), 1, 'intermediate'), -- Emre - Matematik
((SELECT id FROM users WHERE phone = '+491500000005'), 6, 'expert'),    -- Zeynep - Kimya
((SELECT id FROM users WHERE phone = '+491500000005'), 7, 'advanced'), -- Zeynep - Biyoloji
((SELECT id FROM users WHERE phone = '+491500000006'), 3, 'expert'),   -- Can - İngilizce
((SELECT id FROM users WHERE phone = '+491500000006'), 4, 'intermediate'), -- Can - Türkçe
((SELECT id FROM users WHERE phone = '+491500000007'), 5, 'expert'),   -- Ayşe - Fizik
((SELECT id FROM users WHERE phone = '+491500000007'), 1, 'expert'),  -- Ayşe - Matematik
((SELECT id FROM users WHERE phone = '+491500000008'), 8, 'advanced'), -- Mehmet - Tarih
((SELECT id FROM users WHERE phone = '+491500000008'), 9, 'intermediate'), -- Mehmet - Coğrafya
((SELECT id FROM users WHERE phone = '+491500000009'), 7, 'expert'),   -- Deniz - Biyoloji
((SELECT id FROM users WHERE phone = '+491500000009'), 6, 'advanced'), -- Deniz - Kimya
((SELECT id FROM users WHERE phone = '+491500000010'), 10, 'advanced'), -- Okan - Müzik
((SELECT id FROM users WHERE phone = '+491500000011'), 11, 'expert'),  -- Gizem - Resim
((SELECT id FROM users WHERE phone = '+491500000012'), 1, 'expert'),  -- Tolga - Matematik
((SELECT id FROM users WHERE phone = '+491500000012'), 5, 'advanced'), -- Tolga - Fizik
((SELECT id FROM users WHERE phone = '+491500000012'), 12, 'intermediate'), -- Tolga - Bilgisayar
((SELECT id FROM users WHERE phone = '+491500000013'), 2, 'expert'),  -- Burcu - Almanca
((SELECT id FROM users WHERE phone = '+491500000013'), 4, 'advanced'), -- Burcu - Türkçe
((SELECT id FROM users WHERE phone = '+491500000014'), 1, 'expert'),  -- Kerem - Matematik
((SELECT id FROM users WHERE phone = '+491500000015'), 3, 'expert'),  -- Seda - İngilizce
((SELECT id FROM users WHERE phone = '+491500000016'), 6, 'expert'),  -- Onur - Kimya
((SELECT id FROM users WHERE phone = '+491500000016'), 1, 'advanced'), -- Onur - Matematik
((SELECT id FROM users WHERE phone = '+491500000017'), 7, 'expert'),  -- Ebru - Biyoloji
((SELECT id FROM users WHERE phone = '+491500000018'), 9, 'advanced'), -- Arda - Coğrafya
((SELECT id FROM users WHERE phone = '+491500000018'), 8, 'intermediate'), -- Arda - Tarih
((SELECT id FROM users WHERE phone = '+491500000019'), 12, 'expert'), -- Melis - Bilgisayar
((SELECT id FROM users WHERE phone = '+491500000019'), 1, 'advanced'), -- Melis - Matematik
((SELECT id FROM users WHERE phone = '+491500000020'), 2, 'expert');  -- Berk - Almanca

