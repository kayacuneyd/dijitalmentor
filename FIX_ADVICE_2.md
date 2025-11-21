# DijitalMentor Proje Analizi ve Düzeltme Yol Haritası

## 📊 DURUM ÖZETİ

Projenizde **3 katmanlı bir karışıklık** var:

### 🔴 Kritik Sorunlar:
1. **API URL Karmaşası**: Kodda 4 farklı API URL konfigürasyonu var
   - `api.dijitalmentor.de/server/api`
   - `dijitalmentor.de/server/api`
   - Eski Bezmidar referansları

2. **Dosya Organizasyonu**: Frontend ve backend dosyaları karışmış durumda
   - Hostinger'de hem frontend (build/) hem backend (server/api/) var
   - Ama Vercel'de sadece frontend olmalı

3. **Güvenlik Açığı**: Veritabanı şifreniz GitHub'a yüklenmiş (.env.local dosyasında)

4. **Deployment Belirsizliği**:
   - GitHub Actions Hostinger'e deploy ediyor
   - Ama siz Vercel kullanmak istiyorsunuz
   - İkisi çakışıyor

### ✅ İyi Haberler:
- Backend API'niz çalışıyor ve düzgün yapılandırılmış
- SvelteKit frontend'iniz iyi organize edilmiş
- Güzel dokümantasyonlarınız var
- Database şemanız hazır

---

## 🗺️ ÖNERİLEN YOL HARİTASI

Size **3 aşamalı** bir plan hazırladım:

### **AŞAMA 1: Analiz ve Yedekleme** (1-2 saat)

**Hedef**: Mevcut durumu koruyarak tüm dosyaları lokale indirmek

**Adımlar**:
1. Hostinger'deki tüm dosyaları SSH/RSYNC ile indir
2. GitHub repo ile karşılaştır
3. Sadece Hostinger'de olan dosyaları tespit et (production-only files)
4. Güvenlik açıklarını listele

**Çıktı**:
- `hostinger-backup/` klasörü
- Farklılıklar raporu
- Güvenlik kontrol listesi

**Komutlar**:
```bash
# Hostinger'den tüm dosyaları indir
rsync -avz --progress \
  -e "ssh -p 65002 -i ~/.ssh/dijitalmentor_deploy" \
  u553245641@185.224.137.82:~/public_html/ \
  ./hostinger-backup/

# Dizin yapılarını karşılaştır
diff -r ./hostinger-backup/ ./ --brief

# Sadece Hostinger'de olan dosyaları bul
rsync -avn --delete ./ ./hostinger-backup/ | grep "deleting"

# Sadece GitHub'da olan dosyaları bul
rsync -avn --delete ./hostinger-backup/ ./ | grep "deleting"
```

---

### **AŞAMA 2: Mimari Karar ve Temizlik** (2-3 saat)

**Hedef**: Deployment stratejisini netleştir ve güvenlik sorunlarını çöz

**İki Seçenek**:

#### **SEÇENEK A: Herşey Hostinger'de** (Basit ama önerilmez)
- Frontend ve backend aynı yerde
- Mevcut GitHub Actions devam eder
- API URL: `dijitalmentor.de/server/api`
- ✅ Avantaj: Tek yer, kolay yönetim
- ❌ Dezavantaj: Yavaş, ölçeklenebilir değil

**Hostinger Dizin Yapısı (Seçenek A)**:
```
public_html/
├── _app/                    # Frontend bundles (from build/)
├── index.html              # Frontend SPA entry
├── favicon.png
├── logo.svg
├── manifest.json
├── server/api/             # Backend PHP API
├── uploads/                # User uploads
├── .htaccess              # Routing config
└── .env                   # Environment variables
```

#### **SEÇENEK B: Vercel + Hostinger** (ÖNERİLEN)
- Frontend → Vercel (`dijitalmentor.de`)
- Backend → Hostinger subdomain (`api.dijitalmentor.de`)
- ✅ Avantaj: Hızlı, profesyonel, ölçeklenebilir
- ✅ CDN, otomatik SSL, git-based deployment
- ❌ Dezavantaj: DNS ayarı gerekir

**Vercel Dizin Yapısı (Seçenek B - Frontend)**:
```
build/
├── _app/                    # JS/CSS bundles
├── index.html              # SPA entry point
├── favicon.png
├── logo.svg
└── manifest.json
```

**Hostinger Dizin Yapısı (Seçenek B - API Only)**:
```
public_html/
├── server/api/             # Backend PHP API only
├── uploads/                # User uploads
├── .htaccess              # API routing only
└── .env                   # DB credentials, JWT secret
```

**Temizlik Adımları**:
1. `.env.local` dosyasını git'ten kaldır (GÜVENLİK!)
   ```bash
   git rm --cached .env.local
   echo ".env.local" >> .gitignore
   git commit -m "Remove sensitive .env.local from git"
   ```

2. Yeni JWT secret oluştur (32+ karakter)
   ```bash
   # Güçlü bir secret oluştur
   openssl rand -base64 32
   ```

3. `api/` ve `server/api/` karmaşasını düzelt
   - Karar: `server/api/` kullanmaya devam et (mevcut yapı)
   - Eski `api/` referanslarını temizle

4. Bezmidar referanslarını temizle
   - `.env.example` dosyasını güncelle
   - `BEZMIDAR_BRANDING_KIT.md` dosyasını sil

5. `.gitignore` güncelle
   ```
   .env
   .env.local
   .env.production
   /uploads/*
   !/uploads/.gitkeep
   /hostinger-backup/
   ```

---

### **AŞAMA 3: Deployment Uygulama** (3-4 saat)

**Seçenek B için detaylı adımlar**:

#### **3.1 Vercel Kurulumu**
1. GitHub repo'yu Vercel'e bağla
   - https://vercel.com → Import Project
   - GitHub repo seç: `dijitalmentor`

2. Build ayarları:
   - Framework Preset: `SvelteKit`
   - Build Command: `npm run build`
   - Output Directory: `build`
   - Install Command: `npm install`

3. Environment Variables ekle:
   ```
   PUBLIC_API_URL=https://api.dijitalmentor.de/server/api
   PUBLIC_MOCK_MODE=false
   ```

4. Deploy et ve test et
   - Otomatik deployment başlayacak
   - Preview URL'den kontrol et
   - Hata varsa logs'u incele

#### **3.2 Hostinger Yeniden Yapılandırma**

1. Subdomain oluştur: `api.dijitalmentor.de`
   - Hostinger Control Panel → Domains → Subdomains
   - Subdomain: `api`
   - Document Root: `public_html/` (veya `public_html/api/` tercih ederseniz)

2. Frontend dosyalarını temizle (yedek aldıktan sonra):
   ```bash
   # SSH ile Hostinger'e bağlan
   ssh -p 65002 -i ~/.ssh/dijitalmentor_deploy u553245641@185.224.137.82

   # public_html içinde frontend dosyalarını sil
   cd ~/public_html
   rm -rf _app/
   rm -f index.html favicon.png logo.svg manifest.json
   ```

3. Sadece şunları bırak:
   - `server/api/` (backend)
   - `uploads/` (kullanıcı dosyaları)
   - `.htaccess` (sadece API için)
   - `.env` (yeni güvenli credentials ile)

4. `.htaccess` dosyasını API-only olarak güncelle:
   ```apache
   # API yönlendirmeleri
   RewriteEngine On
   RewriteBase /

   # CORS headers
   Header always set Access-Control-Allow-Origin "*"
   Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
   Header always set Access-Control-Allow-Headers "Content-Type, Authorization"

   # API routing
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteRule ^server/api/(.*)$ server/api/$1 [L,QSA]

   # Handle OPTIONS requests
   RewriteCond %{REQUEST_METHOD} OPTIONS
   RewriteRule ^(.*)$ $1 [R=200,L]
   ```

5. `.env` dosyası oluştur (Hostinger'de):
   ```env
   # Database
   DB_HOST=localhost
   DB_NAME=u553245641_dijitalmentor
   DB_USER=u553245641_dijitalmentor
   DB_PASS=YENİ_GÜVENLİ_ŞİFRE

   # JWT
   JWT_SECRET=BURAYA_32_KARAKTER_RANDOM_STRING

   # Environment
   ENVIRONMENT=production
   ```

#### **3.3 DNS Ayarları**

Hostinger DNS Management'de:

1. `dijitalmentor.de` → Vercel
   - Type: `A`
   - Name: `@`
   - Value: `76.76.21.21` (Vercel IP - deploy sonrası Vercel'den alacaksınız)

2. `www.dijitalmentor.de` → Vercel
   - Type: `CNAME`
   - Name: `www`
   - Value: `cname.vercel-dns.com` (Vercel'den alacaksınız)

3. `api.dijitalmentor.de` → Hostinger
   - Type: `A`
   - Name: `api`
   - Value: `185.224.137.82` (Hostinger IP)

**NOT**: DNS değişiklikleri 24-48 saat sürebilir (genelde 1-2 saat)

#### **3.4 Kod Güncellemeleri**

1. API URL'lerini güncelle:

   **Dosya: `.env.example`**
   ```env
   PUBLIC_API_URL=https://api.dijitalmentor.de/server/api
   PUBLIC_MOCK_MODE=false
   ```

   **Dosya: `src/lib/utils/api.js`**
   ```javascript
   const DEFAULT_API_BASE = 'https://api.dijitalmentor.de/server/api';
   ```

2. CORS ayarlarını kontrol et:

   **Dosya: `server/api/config/cors.php`**
   ```php
   $allowedOrigins = [
       'https://dijitalmentor.de',
       'https://www.dijitalmentor.de',
       'https://api.dijitalmentor.de'
   ];
   ```

3. GitHub Actions workflow'u güncelle veya kaldır:

   **Seçenek A**: Kaldır (Vercel otomatik deploy yapar)
   ```bash
   git rm .github/workflows/deploy.yml
   ```

   **Seçenek B**: Sadece API için kullan
   ```yaml
   # .github/workflows/deploy-api.yml
   name: Deploy API to Hostinger

   on:
     push:
       branches: [master]
       paths:
         - 'server/api/**'

   jobs:
     deploy:
       runs-on: ubuntu-latest
       steps:
         - uses: actions/checkout@v3

         - name: Deploy API via FTP
           uses: SamKirkland/FTP-Deploy-Action@4.3.0
           with:
             server: ftp.yourdomain.com
             username: ${{ secrets.FTP_USERNAME }}
             password: ${{ secrets.FTP_PASSWORD }}
             local-dir: ./server/api/
             server-dir: /public_html/server/api/
   ```

4. Commit ve push:
   ```bash
   git add .
   git commit -m "Update API URLs and deployment configuration for Vercel + Hostinger setup"
   git push origin master
   ```

---

## 🔒 GÜVENLİK KONTROL LİSTESİ

### Yapılması Gerekenler:

- [ ] `.env.local` dosyasını git'ten kaldır
- [ ] `.gitignore` dosyasına `.env*` ekle
- [ ] Yeni JWT_SECRET oluştur (32+ karakter)
- [ ] Hostinger'de yeni güçlü database şifresi ayarla
- [ ] `server/api/config/db.php` içindeki hardcoded şifreyi kaldır
- [ ] GitHub secrets'a yeni FTP credentials ekle
- [ ] CORS ayarlarını production domain'leriyle güncelle
- [ ] SQL injection koruması ekle (prepared statements kullan)
- [ ] Rate limiting ekle (brute force koruması)
- [ ] Production'da console.log'ları kaldır

### Kontrol Edilecekler:

- [ ] API endpoints authentication gerektiriyor mu?
- [ ] File upload'larda dosya tipi kontrolü var mı?
- [ ] JWT token expiration süresi uygun mu?
- [ ] Database backup stratejisi var mı?
- [ ] Error messages sensitive bilgi içermiyor mu?

---

## 📋 DEPLOYMENT SONRASI TEST LİSTESİ

### Frontend (Vercel):
- [ ] Ana sayfa yükleniyor mu? (`https://dijitalmentor.de`)
- [ ] Login sayfası çalışıyor mu?
- [ ] Kayıt formu çalışıyor mu?
- [ ] Teacher listing sayfası yükleniyor mu?
- [ ] Console'da CORS hatası var mı?
- [ ] Network tab'de API çağrıları doğru URL'e gidiyor mu?

### Backend (Hostinger):
- [ ] API health check: `https://api.dijitalmentor.de/server/api/subjects/list.php`
- [ ] CORS headers geliyor mu?
- [ ] Database bağlantısı çalışıyor mu?
- [ ] File upload çalışıyor mu?
- [ ] JWT authentication çalışıyor mu?
- [ ] Error handling düzgün çalışıyor mu?

### Integration:
- [ ] Login işlemi başarılı oluyor mu?
- [ ] Token storage çalışıyor mu?
- [ ] Protected routes erişilebiliyor mu?
- [ ] File upload frontend'den çalışıyor mu?
- [ ] Messaging sistemi çalışıyor mu?

---

## 🐛 BİLİNEN HATALAR ve ÇÖZÜMLERİ

### 1. CORS Hatası
**Hata**: `Access-Control-Allow-Origin` header yok
**Çözüm**:
- `server/api/config/cors.php` dosyasını her endpoint'e include et
- `.htaccess` dosyasında CORS headers ekle
- Vercel domain'ini allowed origins'e ekle

### 2. 404 Not Found (API)
**Hata**: API endpoint'leri 404 dönüyor
**Çözüm**:
- `.htaccess` dosyasının doğru yerde olduğunu kontrol et
- Apache mod_rewrite enabled olduğunu kontrol et
- Server logs'u incele

### 3. Database Connection Failed
**Hata**: Cannot connect to database
**Çözüm**:
- `.env` dosyasının doğru yerde olduğunu kontrol et
- Database credentials doğru mu kontrol et
- MySQL service çalışıyor mu kontrol et

### 4. JWT Token Invalid
**Hata**: Token verification failed
**Çözüm**:
- JWT_SECRET her iki tarafta da aynı olmalı
- Token expiration süresini kontrol et
- Token format doğru mu kontrol et (Bearer {token})

---

## ❓ KARAR VERMEK İÇİN SORULAR

Devam etmeden önce şunları netleştirmeliyiz:

1. **Deployment Stratejisi**: Seçenek A mı (herşey Hostinger) yoksa Seçenek B mi (Vercel + Hostinger)?
   - Bütçeniz var mı? (Vercel free tier yeterli olabilir)
   - DNS ayarlarına erişiminiz var mı?

2. **API Subdomain**: `api.dijitalmentor.de` kullanmak ister misiniz yoksa `dijitalmentor.de/server/api` devam mı?

3. **Öncelik**: En önemli sorun nedir?
   - Hataları çözmek mi?
   - Dosya organizasyonunu düzeltmek mi?
   - Deployment'ı otomatikleştirmek mi?

4. **Hostinger Backup**: Dosyaları indirmek için hangi yöntemi tercih edersiniz?
   - SSH/RSYNC (hızlı, güvenli) - ÖNERİLEN
   - FTP (kolay, yavaş)
   - Hostinger File Manager (manuel, sınırlı)

---

## 🎯 ÖNERİ

Size **Seçenek B**'yi (Vercel + Hostinger split) öneriyorum çünkü:

1. ✅ Frontend Vercel'de → Hızlı yükleme, CDN, otomatik SSL
2. ✅ Backend Hostinger'de → PHP desteği, mevcut veritabanı
3. ✅ Temiz ayrım → Her katman kendi işini yapar
4. ✅ Gelecek için ölçeklenebilir
5. ✅ Vercel free tier yeterli (ticari proje değilse)

**İlk adım olarak**: Hostinger dosyalarını SSH ile indirip, GitHub ile karşılaştıralım. Sonra temiz bir deployment yapalım.

---

## 📞 DESTEK KAYNAKLARI

- **Vercel Documentation**: https://vercel.com/docs
- **SvelteKit Deployment**: https://kit.svelte.dev/docs/adapter-static
- **Hostinger PHP Hosting**: https://support.hostinger.com/en/collections/1612745-php
- **DNS Propagation Check**: https://www.whatsmydns.net/

---

## 📝 NOTLAR

- Bu dokümandaki tüm IP adresleri ve credentials örnek amaçlıdır
- Production'a geçmeden önce mutlaka test environment'da deneyin
- Her adımdan önce backup alın
- DNS değişikliklerinden sonra 24-48 saat bekleyin (propagation)
- İlk deployment'tan sonra monitoring ve logging ekleyin

---

**Oluşturulma Tarihi**: 2025-11-21
**Versiyon**: 2.0
**Durum**: Plan Aşaması
