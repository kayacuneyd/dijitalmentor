# BEZMIDAR.DE - TEKNİK GELİŞTİRME ROADMAP (PART 1/2)

## 📋 Proje Teknik Kimliği

**Domain:** bezmidar.de
**Stack:** SvelteKit (Static) + PHP API + MySQL + Capacitor
**Hosting:** Shared Hosting (Hetzner/All-Inkl)
**Development Time:** 6-8 hafta (20 saat/hafta)
**Target:** MVP ilk launch

---

## 🏗️ Mimari Genel Bakış

### Sistem Mimarisi

```
┌─────────────────────────────────────────────────────┐
│                    KULLANICI                         │
│         (Web Browser / iOS / Android)                │
└──────────────────┬──────────────────────────────────┘
                   │
                   │ HTTPS
                   ▼
┌─────────────────────────────────────────────────────┐
│              APACHE/LITESPEED SERVER                 │
│                 (Shared Hosting)                     │
│  ┌────────────────────┐    ┌────────────────────┐  │
│  │   Static Files     │    │    PHP API         │  │
│  │   (SvelteKit)      │    │   (/api/*.php)     │  │
│  │   /public_html     │    │                    │  │
│  └────────────────────┘    └──────┬─────────────┘  │
│                                    │                 │
│                                    ▼                 │
│                          ┌─────────────────┐        │
│                          │  MySQL Database │        │
│                          └─────────────────┘        │
└─────────────────────────────────────────────────────┘
```

### Klasör Yapısı

```
bezmidar.de/
├── .git/
├── .gitignore
├── package.json
├── svelte.config.js
├── vite.config.js
├── capacitor.config.ts
├── tailwind.config.js
├── .env.example
├── .env
│
├── src/                          # SvelteKit Source
│   ├── routes/
│   │   ├── +page.svelte         # Ana sayfa
│   │   ├── +layout.svelte       # Global layout
│   │   ├── giris/
│   │   │   └── +page.svelte     # Giriş
│   │   ├── kayit/
│   │   │   └── +page.svelte     # Kayıt
│   │   ├── ara/
│   │   │   └── +page.svelte     # Arama/Liste
│   │   ├── profil/
│   │   │   └── [id]/
│   │   │       └── +page.svelte # Öğretmen profil
│   │   └── panel/
│   │       ├── +page.svelte     # Dashboard
│   │       └── ayarlar/
│   │           └── +page.svelte # Profil düzenle
│   │
│   ├── lib/
│   │   ├── components/
│   │   │   ├── Navbar.svelte
│   │   │   ├── Footer.svelte
│   │   │   ├── TeacherCard.svelte
│   │   │   ├── FilterSidebar.svelte
│   │   │   └── Modal.svelte
│   │   ├── stores/
│   │   │   ├── auth.js          # Kullanıcı oturumu
│   │   │   ├── teachers.js       # Öğretmen listesi
│   │   │   └── search.js        # Arama filtreleri
│   │   └── utils/
│   │       ├── api.js           # API wrapper
│   │       └── helpers.js       # Yardımcı fonksiyonlar
│   │
│   ├── app.html                  # HTML template
│   └── app.css                   # Global CSS + Tailwind
│
├── static/                       # Statik dosyalar
│   ├── favicon.png
│   ├── logo.svg
│   └── images/
│
├── build/                        # Build output (gitignore)
│
├── android/                      # Capacitor Android
├── ios/                          # Capacitor iOS
│
└── server/                       # Sunucuya deploy edilecek
    ├── public_html/              # Static files buraya
    ├── api/
    │   ├── config/
    │   │   ├── db.php           # DB bağlantı
    │   │   ├── cors.php         # CORS headers
    │   │   └── auth.php         # JWT helper
    │   │
    │   ├── auth/
    │   │   ├── register.php
    │   │   ├── login.php
    │   │   └── verify.php       # Token doğrulama
    │   │
    │   ├── teachers/
    │   │   ├── list.php         # GET tüm öğretmenler
    │   │   ├── detail.php       # GET tek profil
    │   │   ├── create.php       # POST yeni profil
    │   │   └── update.php       # PUT profil güncelle
    │   │
    │   ├── parents/
    │   │   ├── profile.php
    │   │   └── favorites.php
    │   │
    │   ├── subjects/
    │   │   └── list.php         # GET tüm dersler
    │   │
    │   ├── unlock/
    │   │   └── request.php      # POST iletişim talebi
    │   │
    │   ├── reviews/
    │   │   ├── create.php
    │   │   └── list.php
    │   │
    │   └── upload/
    │       └── image.php         # Profil fotoğrafı
    │
    ├── uploads/                  # Kullanıcı yüklemeleri
    │   └── avatars/
    │
    ├── .htaccess                 # SPA rewrite rules
    └── .env                      # Sunucu environment vars
```

---

## 🛠️ Geliştirme Ortamı Kurulumu

### Önkoşullar

```bash
# Node.js 18+ (LTS)
node --version  # v18.0.0+

# npm veya pnpm
npm --version   # 9.0.0+

# Git
git --version

# PHP 8.0+ (Yerel test için)
php --version   # 8.0+

# MySQL (Yerel test için - XAMPP/MAMP)
mysql --version
```

### Proje İnit (İlk Kurulum)

```bash
# 1. Proje klasörü oluştur
mkdir bezmidar
cd bezmidar

# 2. Git init
git init
git branch -M main

# 3. SvelteKit projesi oluştur
npm create svelte@latest .
# Seçenekler:
# - Skeleton project
# - TypeScript: No (hız için)
# - ESLint: Yes
# - Prettier: Yes
# - Vitest: Yes (optional)

# 4. Paketleri yükle
npm install

# 5. TailwindCSS ekle
npx svelte-add@latest tailwindcss
npm install

# 6. Adapter-static ekle
npm install -D @sveltejs/adapter-static

# 7. Capacitor ekle (Mobil için)
npm install @capacitor/core @capacitor/cli
npm install @capacitor/android @capacitor/ios

# 8. İlave kütüphaneler
npm install axios jwt-decode date-fns
npm install -D vite-plugin-pwa  # PWA için (optional)
```

### Environment Variables (.env)

```bash
# .env.example (repo'ya commit edilir)
PUBLIC_API_URL=https://api.bezmidar.de
PUBLIC_SITE_URL=https://bezmidar.de

# .env (gitignore'da, local dev için)
PUBLIC_API_URL=http://localhost:8000/api
PUBLIC_SITE_URL=http://localhost:5173
```

### SvelteKit Config (svelte.config.js)

```javascript
import adapter from '@sveltejs/adapter-static';
import { vitePreprocess } from '@sveltejs/vite-plugin-svelte';

/** @type {import('@sveltejs/kit').Config} */
const config = {
  preprocess: vitePreprocess(),

  kit: {
    adapter: adapter({
      pages: 'build',
      assets: 'build',
      fallback: 'index.html', // SPA mode - ÇOK ÖNEMLİ
      precompress: false,
      strict: true
    }),
    
    // Tüm route'lar client-side olacak
    prerender: {
      entries: []
    }
  }
};

export default config;
```

### Capacitor Config (capacitor.config.ts)

```typescript
import { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'de.bezmidar.app',
  appName: 'Bezmidar',
  webDir: 'build',
  
  server: {
    androidScheme: 'https', // HTTPS şart (security)
    // Development sırasında:
    // url: 'http://192.168.1.100:5173',
    // cleartext: true
  },
  
  plugins: {
    SplashScreen: {
      launchShowDuration: 2000,
      backgroundColor: '#2563eb' // Tailwind blue-600
    }
  }
};

export default config;
```

---

## 📅 Geliştirme Fazları

### HAFTA 1-2: Backend (PHP API) Temel

**Hedef:** Database + Auth + Temel CRUD endpoint'ler

#### Adım 1.1: Database Setup

**database/schema.sql** dosyasını oluştur ve sunucuya yükle:

```sql
-- Schema tam hali eklenecek (karakter sınırı nedeniyle özet)
CREATE DATABASE bezmidar_db;

CREATE TABLE users (...);
CREATE TABLE teacher_profiles (...);
CREATE TABLE subjects (...);
CREATE TABLE teacher_subjects (...);
CREATE TABLE unlock_requests (...);
CREATE TABLE reviews (...);
CREATE TABLE favorites (...);

-- Seed data
INSERT INTO subjects (name, slug) VALUES 
('Matematik', 'matematik'),
('Almanca', 'almanca'),
...
```

#### Adım 1.2: PHP Config Dosyaları

**server/api/config/db.php**

```php
<?php
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'bezmidar_db';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';

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

**server/api/config/cors.php**

```php
<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
```

#### Adım 1.3: Auth Endpoints

Detaylı PHP auth implementasyonu (register.php, login.php, JWT helpers)

#### Adım 1.4: Teacher Endpoints

- list.php: Filtreleme ve pagination
- detail.php: Tek profil
- update.php: Profil düzenleme

---

*PART 2'de devam: Frontend implementasyon, Capacitor, deployment*

*Devam için TECHNICAL_ROADMAP_PART2.md dosyasına bak*
