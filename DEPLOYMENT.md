# Bezmidar - Deployment Guide (Shared Hosting)

Bu rehber, Bezmidar uygulamasını Hostinger gibi shared hosting servislerine deploy etmek için gerekli adımları içerir.

## 📋 Gereksinimler

- **Hosting:** PHP 7.4+ ve MySQL 5.7+ desteği olan shared hosting
- **Git:** Hosting panelinde Git desteği
- **Node.js:** Yerel makinenizde build için (v18+)
- **Database:** MySQL veritabanı

---

## 🚀 Deployment Adımları

### 1. GitHub'a Yükleme

```bash
# Projeyi GitHub'a push edin
git init
git add .
git commit -m "Initial commit"
git branch -M main
git remote add origin https://github.com/KULLANICI_ADINIZ/bezmidar.git
git push -u origin main
```

### 2. Yerel Build Oluşturma

Shared hosting'de Node.js build yapılamayacağı için, önce yerel makinenizde build alın:

```bash
# Bağımlılıkları yükleyin
npm install

# Production build oluşturun
npm run build
```

Bu komut `build/` klasörü oluşturacaktır. Bu klasör frontend'inizin tamamını içerir.

### 3. Veritabanı Kurulumu

#### 3.1 Hostinger'da Veritabanı Oluşturma

1. Hostinger kontrol paneline giriş yapın
2. **Databases** > **MySQL Databases** bölümüne gidin
3. Yeni bir veritabanı oluşturun:
   - **Database Name:** `bezmidar_db` (veya istediğiniz isim)
   - **Username:** Otomatik oluşturulacak
   - **Password:** Güçlü bir şifre belirleyin
4. Veritabanı bilgilerini not edin

#### 3.2 SQL Dosyasını İçe Aktarma

1. **phpMyAdmin**'e gidin (Hostinger panelinden erişilebilir)
2. Oluşturduğunuz veritabanını seçin
3. **Import** sekmesine tıklayın
4. `database/install.sql` dosyasını seçin ve yükleyin
5. **Go** butonuna tıklayın

✅ Veritabanınız artık hazır!

### 4. Backend (PHP API) Yapılandırması

#### 4.1 Veritabanı Bağlantısı

`server/api/config/db.php` dosyasında veritabanı bilgilerinizi güncelleyin:

```php
<?php
$host = 'localhost'; // Genellikle localhost
$dbname = 'VERITABANI_ADINIZ'; // Örn: u123456_bezmidar
$username = 'VERITABANI_KULLANICI_ADI'; // Örn: u123456_bezmidar_user
$password = 'VERITABANI_SIFRENIZ';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(['error' => 'Database connection failed']));
}
```

> **ÖNEMLİ:** Güvenlik için, production ortamında environment variables kullanmanız önerilir. Ancak shared hosting'de bu genellikle mümkün olmadığı için doğrudan değerleri yazabilirsiniz.

#### 4.2 CORS Ayarları

`server/api/config/cors.php` dosyasında domain'inizi ekleyin:

```php
<?php
$allowed_origins = [
    'https://yourdomain.com',
    'https://www.yourdomain.com'
];
```

### 5. Dosyaları Hosting'e Yükleme

#### Yöntem 1: Git ile (Önerilen)

1. Hostinger panelinde **Git** bölümüne gidin
2. Repository URL'nizi girin: `https://github.com/KULLANICI_ADINIZ/bezmidar.git`
3. Branch: `main`
4. Deploy path: `public_html` veya `domains/yourdomain.com/public_html`
5. **Pull** butonuna tıklayın

#### Yöntem 2: FTP/File Manager ile

1. Yerel `build/` klasörünün içeriğini `public_html/` klasörüne yükleyin
2. `server/` klasörünü `public_html/api/` olarak yükleyin

### 6. Klasör Yapısı (Hosting'de)

```
public_html/
├── api/                    # Backend (server/ klasörü)
│   ├── auth/
│   ├── teachers/
│   ├── subjects/
│   ├── requests/
│   └── config/
│       └── db.php         # ← Veritabanı ayarları burada
├── _app/                   # SvelteKit frontend assets
├── index.html              # Ana sayfa
└── ...diğer build dosyaları
```

### 7. Frontend API URL Yapılandırması

`.env` dosyasını düzenleyin (build öncesi):

```env
PUBLIC_API_URL=https://yourdomain.com/api
PUBLIC_MOCK_MODE=false
```

> **ÖNEMLİ:** `PUBLIC_MOCK_MODE=false` yaparak gerçek API'yi kullanmaya başlayın.

Değişiklik yaptıktan sonra tekrar build alın:

```bash
npm run build
```

### 8. .htaccess Yapılandırması (SPA Routing)

`public_html/.htaccess` dosyası oluşturun:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    
    # API isteklerini api/ klasörüne yönlendir
    RewriteRule ^api/(.*)$ api/$1 [L]
    
    # Diğer tüm istekleri index.html'e yönlendir (SPA)
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.html [L]
</IfModule>
```

### 9. Test Etme

1. **Frontend:** `https://yourdomain.com` adresine gidin
2. **API:** `https://yourdomain.com/api/subjects/list.php` adresini test edin
3. **Öğretmen Arama:** Filtreleri kullanarak öğretmen arayın
4. **Kayıt/Giriş:** Test kullanıcısı ile giriş yapın

---

## 🔧 Sorun Giderme

### Problem: "500 Internal Server Error"

**Çözüm:**
- PHP error log'larını kontrol edin (Hostinger panelinden)
- `server/api/config/db.php` dosyasındaki veritabanı bilgilerini doğrulayın
- PHP versiyonunun 7.4+ olduğundan emin olun

### Problem: API istekleri çalışmıyor

**Çözüm:**
- `.env` dosyasında `PUBLIC_API_URL` doğru mu kontrol edin
- CORS ayarlarını kontrol edin (`server/api/config/cors.php`)
- Browser console'da network tab'ı kontrol edin

### Problem: Sayfa yenilediğinde 404 hatası

**Çözüm:**
- `.htaccess` dosyasının doğru yapılandırıldığından emin olun
- `mod_rewrite` modülünün aktif olduğunu kontrol edin

### Problem: Türkçe karakterler bozuk görünüyor

**Çözüm:**
- Veritabanı ve tabloların `utf8mb4_unicode_ci` collation'ı kullandığından emin olun
- PHP dosyalarının UTF-8 encoding ile kaydedildiğinden emin olun

---

## 📝 Güvenlik Önerileri

1. **Veritabanı Şifreleri:** Güçlü ve benzersiz şifreler kullanın
2. **API Güvenliği:** Rate limiting ekleyin (production için)
3. **HTTPS:** SSL sertifikası kullanın (Hostinger ücretsiz sağlar)
4. **Dosya İzinleri:** Hassas dosyaların izinlerini 644 yapın
5. **Error Reporting:** Production'da PHP error reporting'i kapatın

---

## 🔄 Güncelleme Yapmak

Kod değişikliği yaptığınızda:

1. Yerel makinede değişiklikleri yapın
2. GitHub'a push edin
3. Hostinger Git panelinden **Pull** yapın
4. Eğer frontend değişikliği varsa, tekrar build alıp yükleyin

---

## 📞 Destek

Sorun yaşarsanız:
- Hostinger support ile iletişime geçin
- GitHub Issues açın
- [Bezmidar Documentation](https://github.com/KULLANICI_ADINIZ/bezmidar) sayfasını kontrol edin

---

**Başarılar! 🎉**
