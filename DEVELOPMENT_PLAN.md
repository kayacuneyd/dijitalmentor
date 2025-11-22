# DİJİTALMENTOR GELİŞTİRME PLANI (REVİZE)

## Amaç ve yaklaşım
- Odak: mesaj → onay formu → ders başlatma akışını uçtan uca tamamlamak, ardından ödül/teşvik ve premium kontrollerini sağlamlaştırmak.
- Mümkün olan en küçük artımlarla ilerle: önce veri şeması ve backend, sonra arayüz entegrasyonu, en sonda kozmetik/otomasyon.
- Her aşamada üretimle aynı şartlarda manuel doğrulama; otomasyon için hazır komutlar bırak.

## Önceliklendirilmiş yol haritası
1) Mesaj + Onay Formu (zorunlu)  
2) Profil NULL temizliği + CV premium koruması (hızlı kazanç)  
3) Ödül/Teşvik sistemi (takip ve görünürlük)  
4) Opsiyonel sonraki adımlar (embed video, e-posta otomasyonu, ödeme/push)

---

## Faz 1 — Mesaj & Onay Formu

### Migration
`database/migration_add_lesson_agreements.sql`

```sql
CREATE TABLE IF NOT EXISTS lesson_agreements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    sender_id INT NOT NULL,
    recipient_id INT NOT NULL,
    subject_id INT NOT NULL,
    lesson_location ENUM('student_home', 'turkish_center', 'online') NOT NULL,
    lesson_address VARCHAR(255) DEFAULT NULL,
    meeting_platform ENUM('google_meet', 'zoom', 'jitsi', 'other') DEFAULT NULL,
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
```

### Backend
- Platform sabit: yalnızca Jitsi. `lesson_location === 'online'` olduğunda platform `jitsi`.
- Link üretimi: agreement insert sonrası `roomId = 'dm-' . $agreementId . '-' . bin2hex(random_bytes(4));` → `https://meet.jit.si/<roomId>`. İdempotent; accepted olduktan sonra link değişmez.
- `server/api/agreements/create.php`: JWT doğrulama, konuşma sahipliği kontrolü, karşı tarafı otomatik belirleme, Jitsi linki üretip `meeting_link`/`meeting_platform` kaydet.
- `server/api/agreements/respond.php`: status `accepted|rejected|cancelled`, responded_at set, sadece alıcı veya gönderen iptal edebilir, geçmiş statü denetimi.
- `server/api/agreements/list.php`: conversation_id filtresi, son oluşturulan en üstte, kullanıcıya ait olmayan kayıtları filtrele.

### Frontend
- `src/lib/components/AgreementForm.svelte`: subject dropdown, lokasyon seçimi, adres alanı (fiziksel), online seçilince platform alanı otomatik Jitsi/readonly. Ücret/saat girişleri, tarih (opsiyonel), notlar. Kaydet sonrası listeyi yenilemek için `on:success`.
- `src/lib/components/AgreementCard.svelte`: detay görünümü, durum rozetleri, meeting_link butonu (new tab), alıcı için kabul/ret butonları, gönderici için iptal. Online anlaşmalarda “Dersi başlat (Jitsi)” butonu.
- `src/routes/panel/mesajlar/+page.svelte`: form aç/kapat, agreements listesini çek, `subjects` ön yüklemesi, optimistic UI yerine basit refetch.

### Kabul ölçütleri
- Her iki rol de sohbet başlatabiliyor, onay formu gönderebiliyor.
- Jitsi linki online seçiminde otomatik geliyor; fiziki seçimlerde adres zorunlu.
- Kabul/ret akışı mesaj ekranından tamamlanıyor ve durum persist ediliyor.

---

## Faz 2 — Profil NULL temizliği & CV premium koruması

### Profil NULL temizliği
- Backend: `server/api/teachers/detail.php` içinde nullable alanları `null` olarak normalize et (boş string → null).
- Frontend: `src/routes/profil/[id]/+page.svelte` ve `src/lib/components/TeacherCard.svelte` içinde `null`/boş değerleri gizle, “Bilgi mevcut değil” mesajı göster.

### CV premium koruması
- `server/api/upload/cv.php`: sadece öğretmen (rol: student) ve aktif premium (`is_premium=1 && premium_expires_at > NOW()`) için PDF ≤5MB kabul et; hata mesajları net.
- `src/lib/components/CVUpload.svelte`: client-side boyut/tip kontrolü, yükleme barı, mevcut CV linki, premium olmayanlar için modal (10€/yıl, hediye@dijitalmentor.de).
- `src/routes/panel/ayarlar/+page.svelte`: yalnız öğretmenlere göster; yükleme sonrası store’u güncelle.

Kabul ölçütü: Premium olmayan veya süresi biten öğretmenler CV yükleyemiyor; premium olanlar PDF yükleyebiliyor ve link UI’da yenileniyor.

---

## Faz 3 — Ödül/Teşvik Sistemi

### Migration
`database/migration_add_rewards.sql`

```sql
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

CREATE TABLE IF NOT EXISTS rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reward_type ENUM('parent_5h', 'parent_10h', 'parent_15h', 'teacher_voucher') NOT NULL,
    reward_title VARCHAR(200) NOT NULL,
    reward_description TEXT,
    reward_value DECIMAL(10,2) DEFAULT 0,
    hours_milestone INT NOT NULL,
    awarded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_claimed BOOLEAN DEFAULT 0,
    claimed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_type (reward_type),
    INDEX idx_claimed (is_claimed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

INSERT INTO reward_milestones (role, hours_required, reward_type, reward_title, reward_description, reward_value) VALUES
('parent', 5, 'parent_5h', '5 Saat Ödülü', '%10 indirim kuponu', 5.00),
('parent', 10, 'parent_10h', '10 Saat Ödülü', '%15 indirim kuponu + Dijital materyal', 10.00),
('parent', 15, 'parent_15h', '15+ Saat Ödülü', '%20 indirim + 1 saat ücretsiz + Premium', 20.00),
('student', 20, 'teacher_voucher', '20 Saat Hediye Çeki', '10€ Amazon', 10.00),
('student', 50, 'teacher_voucher', '50 Saat Hediye Çeki', '25€ Amazon', 25.00),
('student', 100, 'teacher_voucher', '100 Saat Hediye Çeki', '50€ Amazon', 50.00);
```

### Backend
- `server/api/rewards/track_hours.php`: agreement_id doğrula (accepted olmalı), hours_completed topla, milestone kontrolü yapıp yeni reward oluştur, toplam saat döndür.
- `server/api/rewards/list.php`: kullanıcının toplam saatini, mevcut ödüllerini ve sıradaki milestone’u döndür.
- `server/api/rewards/claim.php`: ödülün sahibini doğrula, `is_claimed` set et; e-posta gönderimi için hook bırak (opsiyonel).

### Frontend
- `src/lib/components/RewardsPanel.svelte`: toplam saat, ilerleme çubuğu, ödül kartları, claim butonu.
- `src/routes/panel/+page.svelte`: panelde komponenti göster; track/list/claim çağrıları için basit refresh akışı.

Kabul ölçütü: Saat eklendiğinde doğru toplam görünüyor, milestone’da ödül ekleniyor, claim sonrası ödül “talep edildi” olarak işaretleniyor.

---

## Operasyon ve kalite
- Migration sırası: `lesson_agreements` → `rewards`.
- `uploads/cvs` klasörü (755) ve `.gitignore` kontrolü.
- Testler (manuel): mesaj/onay akışı, premium CV yükleme, ödül saat girdisi ve claim.
- Komut önerisi: `npm run build` öncesi `npm run lint` yoksa atlanabilir; build sonrası deploy pipeline otomatik.

---

## Askıya alınan / sonraya bırakılanlar
- Google Meet/Zoom API entegrasyonu (OAuth + ücret/kota) — hook hazır, şimdilik Jitsi.
- Ödül e-posta otomasyonu (SMTP/PHPMailer).
- Video call embed (iframe) ve push notification.
- Ödeme sistemi (Stripe/PayPal) ve otomatik premium aktivasyonu.

---

**Son düzenleme:** 22.11.2025  
**Hazırlayan:** Codex  
**Versiyon:** 1.1
