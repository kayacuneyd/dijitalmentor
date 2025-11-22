# MESAJLAŞMA SORUNU - ACIL ÇÖZÜM

## Sorun
Mesajlar listesi görünüyor ama mesaj içeriği "Database error occurred" hatası veriyor.

## Neden
Production database'de `conversations` tablosunda gerekli kolonlar eksik:
- `last_message_text`
- `last_message_at`
- `teacher_unread_count`
- `parent_unread_count`

## Çözüm

### 1. Database Migration (ÖNCELİKLE BUNU YAPIN!)

Production database'de şu SQL'i çalıştırın:

```sql
-- Add last_message tracking fields to conversations table
ALTER TABLE `conversations`
ADD COLUMN `last_message_text` text DEFAULT NULL AFTER `parent_id`,
ADD COLUMN `last_message_at` timestamp NULL DEFAULT NULL AFTER `last_message_text`,
ADD COLUMN `teacher_unread_count` int(11) DEFAULT 0 AFTER `last_message_at`,
ADD COLUMN `parent_unread_count` int(11) DEFAULT 0 AFTER `teacher_unread_count`;
```

**NOT:** Eğer bu kolonlar zaten varsa (hata alırsanız), yukarıdaki komutu tek tek deneyin:

```sql
-- Tek tek eklemek için:
ALTER TABLE `conversations` ADD COLUMN `last_message_text` text DEFAULT NULL;
ALTER TABLE `conversations` ADD COLUMN `last_message_at` timestamp NULL DEFAULT NULL;
ALTER TABLE `conversations` ADD COLUMN `teacher_unread_count` int(11) DEFAULT 0;
ALTER TABLE `conversations` ADD COLUMN `parent_unread_count` int(11) DEFAULT 0;
```

### 2. Backend Dosyaları Güncelleme

Aşağıdaki dosyaları production sunucunuza yükleyin:

**Yeni Dosyalar:**
- `server/api/centers/list.php`

**Güncellenmiş Dosyalar:**
- `server/api/messages/start.php` (otomatik mesaj + debug logs)
- `server/api/messages/list.php` (debug logs)
- `server/api/messages/detail.php` (debug logs)
- `server/api/agreements/create.php` (Turkish center desteği)
- `server/api/agreements/list.php` (Turkish center bilgileri)

### 3. Turkish Centers (İsteğe Bağlı - Şimdilik Atlayabilirsiniz)

İlerisi için Turkish centers veritabanını eklemek isterseniz:
```sql
-- database/migrations/create_turkish_centers.sql dosyasını çalıştırın
-- database/migrations/add_turkish_center_id_to_lesson_agreements.sql dosyasını çalıştırın
```

### 4. Test Adımları

Migration'ı çalıştırdıktan sonra:

1. Herhangi bir ders talebine girin
2. "İletişime Geç" butonuna tıklayın
3. Mesajlaşma sayfası açılmalı
4. **Otomatik başvuru mesajını görmelisiniz:** "Merhaba, '[Talep Başlığı]' ders talebinize başvurmak istiyorum. - [Öğretmen Adı]"
5. Mesaj yazıp gönderebilmelisiniz

## Beklenen Sonuç

✅ Conversation listesi görünür
✅ Conversation seçildiğinde mesajlar yüklenir
✅ Otomatik başvuru mesajı gönderilir
✅ Mesaj yazıp gönderilebilir

## Sorun Devam Ederse

Server error log'larını kontrol edin:
- `=== Conversation List Request ===`
- `=== Message Detail Request ===`
- `=== Conversation Start Request ===`

Log'larda şunları arayın:
- "User ID: X"
- "Conversation found: YES/NO"
- "Database error"

## Özet

**EN ÖNEMLİ:** Production database'de conversations tablosuna 4 kolon ekleyin. Bunlar olmadan mesajlaşma çalışmaz!
