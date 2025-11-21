# Yol Haritası

Bu projeyi Vercel (frontend) + Hostinger (PHP API + MySQL) yapısına taşırken izlenecek adımlar.

## 1) Backend (Hostinger) Hazırlığı
- `server/api/config/db.php` deployed olsun; `.env` veya `.htaccess` ile env yüklenebiliyor.
- `public_html/.env` (veya `.htaccess` içine `SetEnv`) içerikleri:
  ```
  DB_HOST=localhost
  DB_NAME=u553245641_dijitalmentor
  DB_USER=u553245641_dijitalmentor
  DB_PASS=<sifre>
  JWT_SECRET=<güçlü_random>
  ```
- CORS: `config/cors.php` yüklü olmalı; `curl -I https://dijitalmentor.de/server/api/requests/list.php` çıktısında `Access-Control-Allow-Origin` gör.
- Routing: `public_html/.htaccess` SPA fallback ve `server/api` passthrough kurallarıyla yüklü olmalı.
- Sağlık kontrolleri: `subjects/list.php`, `requests/list.php`, `auth/login.php` (test kullanıcıyla) 200 + JSON dönmeli.

## 2) Domain Stratejisi (dijitalmentor.de uygulama adresi kalsın)
- Ziyaretçiler için `dijitalmentor.de` (ve `www`) Vercel’e yönlensin.
- API için ayrı bir subdomain ayır: `api.dijitalmentor.de` Hostinger’a yönlensin.
- Vercel env: `PUBLIC_API_URL=https://api.dijitalmentor.de`
- CORS: API `Access-Control-Allow-Origin: https://dijitalmentor.de` ve `https://www.dijitalmentor.de` (gerekirse `https://app.dijitalmentor.de` de eklenebilir).
- DNS: `A/CNAME` kayıtlarıyla root + www Vercel, `api` Hostinger’a.

## 3) Frontend’i Vercel’e Deploy
- Vercel Project Settings → Environment Variables:
  - `PUBLIC_API_URL=https://dijitalmentor.de/server/api`
  - `PUBLIC_MOCK_MODE=false`
- Build komutu: `npm run build`
- Output: `build/` (adapter-static) ve fallback `index.html` (SPA) zaten ayarlı.
- Domain: `frontend.domain.com` → Vercel; `api.domain.com` (veya `dijitalmentor.de`) → Hostinger.
- Deploy sonrası kontrol: `/ders-talepleri`, giriş/kayıt, öğretmen listesi; Network tab’da API çağrıları 200 olmalı.

## 3) Güvenlik ve Stabilite
- `JWT_SECRET` ve DB şifreleri commit dışı, sadece env’de.
- Rate limiting (login/register/requests) Hostinger’da aktif; logları kontrol et.
- Geocoding: Hostinger outbound kısıtlıysa fallback koordinatlar devreye girer; engel varsa not alın.

## 4) Yerel Geliştirme
- `.env.local` örnek:
  ```
  PUBLIC_API_URL=https://dijitalmentor.de/server/api
  PUBLIC_MOCK_MODE=false
  ```
- API’yi lokal çalıştıracaksanız: `DB_` env’lerini lokal DB’ye göre verin.

## 5) İzleme ve Hata Ayıklama
- Hostinger PHP error loglarını periyodik kontrol.
- Vercel’de console error olmamalı; GA/DoubleClick 404’leri kritik değil ama temizlenebilir.

## 6) Opsiyonel İyileştirmeler
- API’yi küçük bir VPS/managed service’e taşıyarak ortam değişkeni yükleme ve log erişimini kolaylaştırma.
- Node/Serverless backend’e geçiş düşünülürse PHP kaldırılabilir; şu an PHP API gereklidir.
