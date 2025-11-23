# Dijital Mentor Podcast Automation

Tam otomatik podcast üretim ve dağıtım sistemi.

## 📋 Genel Bakış

Bu sistem, sadece bir konu başlığı girdiğinizde otomatik olarak:
- ✅ Claude AI ile senaryo oluşturur
- ✅ ElevenLabs ile Türkçe seslendirme yapar
- ✅ Fon müziği ekler ve mix yapar
- ✅ Cloudflare R2'ye yükler
- ✅ YouTube'a video olarak yükler
- ✅ Spotify RSS feed'i günceller

**Tahmini Süre:** 5-8 dakika (tamamen otomatik)

## 🚀 Hızlı Başlangıç

### 1. Gereksinimler

- Node.js 20+
- FFmpeg
- API Anahtarları (Anthropic, ElevenLabs, R2, YouTube)

### 2. Kurulum

```bash
cd automation/podcast
npm install
cp .env.example .env
# .env dosyasını düzenle ve API anahtarlarını ekle
```

### 3. Lokal Test

```bash
node generate.js 1 "Almanya eğitim sistemi" "Eğitim Rehberi" "Almanya'daki okul türleri"
```

### 4. Admin Panel'den Kullanım

1. [dijitalmentor.de/panel/admin](https://dijitalmentor.de/panel/admin) → "Podcast Yönetimi" tab'ına git
2. "+ Yeni Podcast Oluştur" butonuna tıkla
3. Konu başlığını gir
4. "Oluştur ve Yayınla" butonuna tıkla
5. **Bekle (5-8 dakika)** - Status otomatik güncellenecek

## 📁 Proje Yapısı

```
automation/podcast/
├── package.json          # Dependencies
├── .env.example          # Environment variables template
├── generate.js           # Main orchestration script
├── clients/
│   ├── anthropic.js      # Claude API client
│   ├── elevenlabs.js     # Text-to-speech client
│   ├── r2.js            # Cloudflare R2 storage
│   └── youtube.js        # YouTube upload
├── utils/
│   ├── ffmpeg.js         # Audio mixing utilities
│   └── helpers.js        # Webhook, logging
├── temp/                 # Temporary files (gitignored)
└── output/               # Generated podcasts (gitignored)
```

## 🔧 API Anahtarlarını Alma

### Anthropic Claude API

1. [console.anthropic.com](https://console.anthropic.com) → API Keys
2. "Create Key" → Anahtarı kopyala
3. `.env` dosyasına `ANTHROPIC_API_KEY=sk-ant-api03-xxx` olarak ekle

**Maliyet:** ~$0.015/episode (Sonnet 4.5)

### ElevenLabs API

1. [elevenlabs.io](https://elevenlabs.io) → Hesap Aç
2. Profile → API Key → Kopyala
3. Voice Library → Türkçe ses seç (örn: "Ahmet") → Voice ID'yi kopyala
4. `.env` dosyasına ekle:
   ```
   ELEVENLABS_API_KEY=xxx
   ELEVENLABS_VOICE_ID=JBFqnCBsd6RMkjVDRZzb
   ```

**Maliyet:** ~$0.30/episode (10k karakter, Creator plan)

### Cloudflare R2

1. [dash.cloudflare.com](https://dash.cloudflare.com) → R2
2. "Create bucket" → `dijitalmentor-podcast`
3. "Manage R2 API Tokens" → "Create API Token"
4. Custom domain bağla: `podcast.dijitalmentor.de`
5. `.env` dosyasına ekle

**Maliyet:** **ÜCRETSİZ** (10GB storage, sınırsız egress)

### YouTube Data API

**Detaylı Setup:** [docs/YOUTUBE_SETUP.md](../../docs/YOUTUBE_SETUP.md)

1. [console.cloud.google.com](https://console.cloud.google.com)
2. Yeni proje oluştur
3. YouTube Data API v3'ü aktifleştir
4. OAuth 2.0 credentials oluştur
5. Refresh token al (bir kerelik)
6. `.env` dosyasına ekle

**Maliyet:** **ÜCRETSİZ** (10,000 units/day, 1 upload = ~1,600 units)

### GitHub Token (Workflow Dispatch için)

1. GitHub → Settings → Developer settings → Personal access tokens
2. "Generate new token (classic)"
3. Permissions: `repo`, `workflow`
4. `.env` dosyasına `GITHUB_TOKEN=ghp_xxx` ekle

## 🎙️ Ses Ayarları

### ElevenLabs Ses Seçimi

**Önerilen Türkçe Sesler:**
- **Ahmet** (Erkek, samimi): Veli podcastleri için ideal
- **Ayşe** (Kadın, profesyonel): Eğitim içerikleri için
- **Multilingual v2** (Herhangi): En doğal Türkçe telaffuz

Voice ID'yi bulmak için:
```bash
node -e "import('./clients/elevenlabs.js').then(m => new m.default().listVoices().then(console.log))"
```

### Ses Klonlama (İsteğe Bağlı)

Kendi sesinizi kullanmak isterseniz:
1. ElevenLabs → Voice Lab → "Instant Voice Cloning"
2. 10-15 dakikalık temiz ses kaydı yükle
3. Yeni Voice ID'yi kopyala ve `.env` dosyasına ekle

**Avantajlar:**
- Marka kimliği
- Daha doğal ton
- Tutarlılık

## 🎵 Fon Müziği

### Otomatik İndirme

`.env` dosyasına URL ekleyin:
```bash
BACKGROUND_MUSIC_URL=https://pixabay.com/music/download/ambient/lofi-study-112191/
```

### Manuel Dosya

`assets/background_music.mp3` olarak kaydedin (URL yerine bu kullanılır).

**Önerilen Müzik Stilleri:**
- Lo-fi Hip Hop
- Ambient Piano
- Acoustic Guitar
- Chill Electronic

**Kaynaklar:**
- [Pixabay Music](https://pixabay.com/music/) (ücretsiz)
- [YouTube Audio Library](https://studio.youtube.com/channel/UCxxxxxx/music) (ücretsiz)
- [Epidemic Sound](https://www.epidemicsound.com/) (ücretli, profesyonel)

## 🔄 Workflow Akışı

```
[Admin Panel]
    ↓ (Form gönder)
[Backend API: /admin/podcast/create.php]
    ↓ (GitHub Actions trigger)
[GitHub Actions Workflow]
    ↓
┌─────────────────────────────────────┐
│ 1. Senaryo Üretimi (Claude)        │
│ 2. Seslendirme (ElevenLabs)        │
│ 3. Müzik Mix (FFmpeg)               │
│ 4. R2 Upload (Cloudflare)          │
│ 5. YouTube Upload (Google API)     │
│ 6. Database Update (Webhook)       │
└─────────────────────────────────────┘
    ↓
[Episode Completed] ✅
    ↓ (RSS otomatik güncellenir)
[Spotify'da Yayında] 🎧
```

## 🐛 Sorun Giderme

### Hata: "Anthropic API timeout"

**Çözüm:** Script tekrar çalıştırılabilir, idempotent.

### Hata: "ElevenLabs quota exceeded"

**Çözüm:**
- Free tier: Ayda 10k karakter (2-3 episode)
- Creator tier ($5/ay): 30k karakter (~6-8 episode)

### Hata: "YouTube upload failed: quota exceeded"

**Çözüm:**
- Günlük limit: 6 video (10,000 units)
- Yarın tekrar dene veya quota artırma iste

### Hata: "R2 upload permission denied"

**Çözüm:** API token permissions kontrol et (Read & Write).

### Hata: "FFmpeg not found"

**Çözüm:**
```bash
# Mac
brew install ffmpeg

# Ubuntu
sudo apt-get install ffmpeg

# GitHub Actions (otomatik kurulu)
```

## 📊 Maliyet Analizi

**Aylık 4 episode için:**
- Anthropic Claude: $0.06
- ElevenLabs: $1.20 (veya $5/ay subscription)
- Cloudflare R2: $0 (ücretsiz tier)
- YouTube: $0
- Spotify: $0
- GitHub Actions: $0 (2,000 dakika/ay ücretsiz)

**TOPLAM: ~$1.30/ay** (4 episode)
**Veya: ~$5.06/ay** (ElevenLabs Creator subscription ile sınırsız)

## 🚦 Durum Takibi

### Admin Panel'den

1. "Podcast Yönetimi" tab'ı açın
2. Episode listesinde durumu görün:
   - ⏳ **Bekliyor**: Henüz başlamadı
   - ⚙️ **Oluşturuluyor**: Pipeline çalışıyor
   - ✅ **Hazır**: Tamamlandı
   - ❌ **Hata**: Başarısız

3. Otomatik polling her 15 saniyede günceller

### GitHub Actions'dan

1. [github.com/thomasmuentzer/dijitalmentor/actions](https://github.com/thomasmuentzer/dijitalmentor/actions)
2. "Podcast Generation Pipeline" workflow'una tıkla
3. Logları görüntüle

## 🔐 Güvenlik

- **API Anahtarları:** GitHub Secrets'da sakla, asla commit etme
- **Webhook Secret:** `.env`'de güçlü bir secret kullan
- **Database:** JWT token ile korumalı endpoint'ler

## 📚 Daha Fazla Bilgi

- [Admin Kullanım Kılavuzu](../../docs/PODCAST_ADMIN_GUIDE.md)
- [API Dokümantasyonu](../../docs/PODCAST_API.md)
- [YouTube Setup Rehberi](../../docs/YOUTUBE_SETUP.md)
- [Troubleshooting](../../docs/PODCAST_TROUBLESHOOTING.md)

## 🤝 Katkıda Bulunma

Önerileriniz için:
- GitHub Issues: [github.com/thomasmuentzer/dijitalmentor/issues](https://github.com/thomasmuentzer/dijitalmentor/issues)
- Email: info@dijitalmentor.de

## 📄 Lisans

Bu proje Dijital Mentor platformunun bir parçasıdır.
