# FIX_ADVICE_1

Lokal repo ve Hostinger/Vercel dağıtımları arasındaki karışıklığı gidermek için öneriler ve yol haritası.

## Genel tespitler
- Frontend SvelteKit statik build üretiyor (`svelte.config.js`), PHP API tek kaynak olarak `server/api/`.
- CORS/env yüklemesi `.env` veya `.htaccess SetEnv` ile çalışıyor (`server/api/config/cors.php`, `server/api/config/db.php`).
- Deploy scripti (`deploy.sh`) Hostinger’a `build/` klasörünü rsync’liyor; API klasörünün aynı public_html altında olduğu varsayılmış.
- DB şeması `database/last_database.sql`, mesajlaşma migrasyonu `database/migration_add_messaging.sql`.

## Hostinger dosyalarını lokale çekip kıyaslama
1) Snapshot al:
   ```bash
   mkdir -p hostinger_snapshot
   rsync -avz -e "ssh -p 65002 -i ~/.ssh/dijitalmentor_deploy" \
     u553245641@185.224.137.82:public_html/ hostinger_snapshot/
   ```
   Eğer `api.dijitalmentor.de` farklı bir public_html altındaysa aynı komutu o path için çalıştır.
2) Diff ile kıyasla:
   ```bash
   git diff --no-index hostinger_snapshot server | head
   ```
   (Gerekiyorsa `build/` ve runtime dosyalarını diff dışına al.)

## Yol haritası (sıra önerisi)
1) API tekilleştir: `server/api`’yi gerçek kaynak yap; başka PHP API kopyalarını (legacy `api/` vb.) ya taşı ya arşivle.
2) Env/DB: Hostinger’da `.env` veya `.htaccess SetEnv` ile `DB_HOST/DB_NAME/DB_USER/DB_PASS/JWT_SECRET` tanımla. Prod’ta repo içindeki şifreleri kullanma.
3) Şema/migrasyon: phpMyAdmin’de `last_database.sql` çalıştır, ardından `migration_add_messaging.sql` (conversations/messages). Eksik tablo varsa (örn. unlock_requests) ekle.
4) CORS: Whitelist’te `https://dijitalmentor.de`, `https://www.dijitalmentor.de`, `https://api.dijitalmentor.de` var; preview/localhost gerekiyorsa env flag’leriyle aç (`ALLOW_VERCEL_PREVIEW`, `ALLOW_LOCALHOST_ORIGINS`).
5) Sağlık kontrolleri: `subjects/list.php`, `requests/list.php`, `auth/login.php` uçlarını 200/JSON + CORS header’ı ile doğrula.
6) Frontend deploy: Vercel env’de `PUBLIC_API_URL=https://api.dijitalmentor.de/server/api` (absolute URL, relative verme). Preview’larda da aynı absolute değer.
7) Temizlik/dokümantasyon: Hostinger diff’inden çıkan gereksiz dosyaları temizle; gerçek kurulum adımlarını `README.md`/`YOLHARITASI.md` ile uyumlu hale getir. Marka dışı/legacy dokümanları arşivle.

## Doğrulama adımları
- Browser Network: Vercel build’iyle `https://api.dijitalmentor.de/server/api/subjects/list.php` 200, CORS hatasız.
- API logları: Hostinger PHP error loglarında fatal yok, rate-limit blokları yok.
- DB: `users`, `teacher_profiles`, `lesson_requests`, `conversations/messages` tablolarında örnek kayıtlar gözüküyor.
- Diff: `hostinger_snapshot` ile repo arasında yalnızca beklenen farklar (env, build) kalıyor.
