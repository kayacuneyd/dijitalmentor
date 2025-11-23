# 🎙️ Podcast Sistemi - Hızlı Başlangıç

## ✅ Yapılması Gerekenler (İlk Kurulum)

### 1. Database Migration (Bir Kerelik)
```bash
# phpMyAdmin'den çalıştır
mysql -u u553245641_dijitalmentor -p u553245641_dijitalmentor < database/migration_add_podcast.sql
```

### 2. Cloudflare R2 Setup (Bir Kerelik)
1. dash.cloudflare.com → R2 → "Create bucket"
   - Bucket adı: `dijitalmentor-podcast`
2. "Manage R2 API Tokens" → "Create API Token"
   - Permissions: Object Read & Write
   - **NOT AL:** Access Key ID, Secret Access Key
3. Custom Domain Bağla:
   - R2 bucket → Settings → Public access → Add custom domain
   - Domain: `podcast.dijitalmentor.de`
   - Cloudflare DNS'de CNAME ekle

### 3. API Anahtarlarını Al

**Anthropic Claude:**
- console.anthropic.com → API Keys → "Create Key"
- Anahtarı kopyala: `sk-ant-api03-xxxxx`

**ElevenLabs:**
- elevenlabs.io → Profile → API Key
- Voice Library → "Ahmet" (Türkçe) → Voice ID'yi kopyala

**YouTube:**
- console.cloud.google.com → YouTube Data API v3 aktif et
- OAuth 2.0 credentials oluştur
- Refresh token al (detaylı: [YOUTUBE_SETUP.md](./YOUTUBE_SETUP.md))

### 4. GitHub Secrets Ekle

GitHub Repo → Settings → Secrets and variables → Actions → "New repository secret"

Eklenecek secret'lar:
```
ANTHROPIC_API_KEY
ELEVENLABS_API_KEY
ELEVENLABS_VOICE_ID
CLOUDFLARE_R2_ACCOUNT_ID
CLOUDFLARE_R2_ACCESS_KEY_ID
CLOUDFLARE_R2_SECRET_ACCESS_KEY
CLOUDFLARE_R2_BUCKET_NAME
CLOUDFLARE_R2_PUBLIC_URL
YOUTUBE_CLIENT_ID
YOUTUBE_CLIENT_SECRET
YOUTUBE_REFRESH_TOKEN
WEBHOOK_URL
WEBHOOK_SECRET
BACKGROUND_MUSIC_URL (opsiyonel)
```

### 5. Backend Deploy

```bash
# Hostinger'a deploy et
./deploy_to_hostinger.sh "Add podcast API endpoints"
```

### 6. Frontend Deploy

```bash
# Git push yeterli (Vercel otomatik deploy eder)
git add .
git commit -m "Add podcast system"
git push origin master
```

---

## 🚀 Podcast Oluşturma (Günlük Kullanım)

### Yöntem 1: Admin Panel (Önerilen)

1. [dijitalmentor.de/panel/admin](https://dijitalmentor.de/panel/admin) → Login
2. "🎙️ Podcast Yönetimi" tab'ına git
3. "+ Yeni Podcast Oluştur" butonu
4. Form doldur:
   - **Konu Başlığı:** "Almanya'da Gymnasium seçimi nasıl yapılır?"
   - **Başlık:** (Opsiyonel, AI oluşturur)
   - **Açıklama:** (Opsiyonel, AI oluşturur)
   - **Yayın Tarihi:** Bugün
   - **Hemen yayınla:** ✅
5. "Oluştur ve Yayınla" → **Bekle 5-8 dakika**
6. Status otomatik güncellenecek: ⚙️ → ✅

### Yöntem 2: Lokal Test (Development)

```bash
cd automation/podcast
node generate.js <episode_id> "<konu_başlığı>" "[başlık]" "[açıklama]"

# Örnek:
node generate.js 1 "Almanya eğitim sistemi" "Eğitim Rehberi" "Almanya'daki okul türleri"
```

---

## 📊 Podcast Takip

### Admin Panel'den

- **Durum:**
  - ⏳ Bekliyor
  - ⚙️ Oluşturuluyor (5-8 dk)
  - ✅ Hazır
  - ❌ Hata

- **Linkler:**
  - 🎵 MP3 (direkt dinle)
  - 📺 YouTube (video)
  - 🌐 Website (podcast/[slug])

### Kullanıcı Tarafında

- [dijitalmentor.de/podcast](https://dijitalmentor.de/podcast) → Tüm bölümler
- [dijitalmentor.de/podcast/[slug]](https://dijitalmentor.de/podcast/almanya-egitim-sistemi) → Tek bölüm
- RSS Feed: `https://dijitalmentor.de/podcast/feed.xml`

---

## 🎧 Spotify'a Ekleme (Bir Kerelik)

1. [podcasters.spotify.com](https://podcasters.spotify.com) → "Add your podcast"
2. RSS Feed URL gir: `https://dijitalmentor.de/podcast/feed.xml`
3. Podcast bilgilerini doldur:
   - Name: Dijital Mentor Podcast
   - Language: Turkish
   - Category: Education
4. "Submit" → Onay bekle (1-2 gün)
5. Onaylandıktan sonra, her yeni episode otomatik eklenir (4-8 saat içinde)

---

## ❗ Sık Sorunlar

### "Episode oluşturma başarısız"
- GitHub Actions loglarına bak: [Actions Tab](https://github.com/thomasmuentzer/dijitalmentor/actions)
- API anahtarları doğru mu kontrol et
- GitHub Secrets eksiksiz mi kontrol et

### "YouTube yüklenemedi"
- Quota doldu mu? (10,000 units/day = 6 video)
- OAuth token süresi dolmuş olabilir → Yenile

### "Episode pending'de kaldı"
- GitHub Actions manuel tetikle:
  - Actions → "Podcast Generation Pipeline" → "Run workflow"
  - Episode ID, topic_prompt gir

### "Ses kalitesi düşük"
- ElevenLabs'de başka ses dene (Voice Library)
- Stability ayarını değiştir (0.3-0.7 arası)

---

## 💰 Maliyetler (Aylık 4 Episode)

| Servis | Maliyet |
|--------|---------|
| Anthropic Claude | $0.06 |
| ElevenLabs | $1.20 veya $5/ay (Creator) |
| Cloudflare R2 | $0 (ücretsiz) |
| YouTube | $0 |
| Spotify | $0 |
| GitHub Actions | $0 |
| **TOPLAM** | **~$1.30/ay** |

---

## 🔗 Faydalı Linkler

- **Admin Panel:** [dijitalmentor.de/panel/admin](https://dijitalmentor.de/panel/admin)
- **Podcast Sayfası:** [dijitalmentor.de/podcast](https://dijitalmentor.de/podcast)
- **GitHub Actions:** [Actions Tab](https://github.com/thomasmuentzer/dijitalmentor/actions)
- **Cloudflare R2:** [dash.cloudflare.com](https://dash.cloudflare.com/r2)
- **YouTube Studio:** [studio.youtube.com](https://studio.youtube.com)
- **Spotify Podcasters:** [podcasters.spotify.com](https://podcasters.spotify.com)

---

## 📞 Destek

- Email: info@dijitalmentor.de
- GitHub Issues: [github.com/thomasmuentzer/dijitalmentor/issues](https://github.com/thomasmuentzer/dijitalmentor/issues)
