# Proje Kurtarma ve Dağıtım (Deployment) Stratejisi

Yaşadığınız karmaşıklığı çok iyi anlıyorum. Şu an projeniz 3 parçaya bölünmüş durumda ve her birinin yeri farklı olmalı. Bu planla karmaşıklığı giderip, "Tek Kaynak" (Single Source of Truth) yapısına geçeceğiz.

### Adımlar

1.  **Güvenli Yedekleme ve Kıyaslama (Hostinger -> Lokal)**
    *   Proje ana dizininde `_hostinger_yedek` adında yeni bir klasör oluşturun ve `.gitignore` dosyasına ekleyin.
    *   Hostinger'daki (`api.dijitalmentor.de` ve ana dizin) tüm dosyaları FTP (FileZilla vb.) ile bu klasöre indirin.
    *   VS Code'da sağ tık -> "Select for Compare" özelliğini kullanarak, `_hostinger_yedek` içindeki PHP dosyaları ile projenizdeki `server/` klasörünü kıyaslayın. Eksikleri `server/` klasörüne taşıyın.

2.  **Backend Temizliği (Hostinger: api.dijitalmentor.de)**
    *   Lokaldeki `server/` klasörünüz artık en güncel hal olmalı.
    *   Hostinger'da `api.dijitalmentor.de` subdomain'inin içini tamamen temizleyin.
    *   Lokaldeki `server` klasörünü, subdomain'in ana dizinine (`public_html` veya subdomain klasörü) olduğu gibi yükleyin.
    *   **Hedef Yapı:** `https://api.dijitalmentor.de/server/api/...` şeklinde erişilebilir olmalı.

3.  **Frontend Kurulumu (Vercel)**
    *   `deploy.sh` dosyasını artık kullanmayın (bu eski Hostinger frontend yüklemesi içindi).
    *   GitHub reponuzu Vercel'e bağlayın.
    *   Vercel proje ayarlarında **Environment Variables** kısmına şunu ekleyin:
        *   `VITE_API_URL`: `https://api.dijitalmentor.de/server/api`
    *   Vercel otomatik olarak `src/` klasörünü derleyip yayına alacaktır.

4.  **Veritabanı Kontrolü**
    *   `database/` klasöründeki `.sql` dosyaları sadece yedek ve kurulum içindir, sunucuya yüklenmesine gerek yoktur (ancak phpMyAdmin üzerinden import edilmelidir).

### Dikkat Edilmesi Gerekenler

1.  **CORS Ayarı:** `server/config/cors.php` dosyasının Vercel domain'inden gelen isteklere izin verdiğinden emin olacağız (bunu birlikte kontrol edebiliriz).
2.  **Svelte Adapter:** Şu an `adapter-static` kullanıyorsunuz. Vercel için `adapter-auto` veya `adapter-vercel`'e geçmek daha sağlıklı olabilir.
