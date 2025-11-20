# BEZMIDAR.DE - TEKNİK GELİŞTİRME ROADMAP (PART 2/2)

## HAFTA 3-4: Frontend (SvelteKit) Geliştirme

### Adım 2.1: Auth Store ve API Wrapper

**src/lib/stores/auth.js**

```javascript
import { writable } from 'svelte/store';
import { browser } from '$app/environment';
import { goto } from '$app/navigation';

function createAuthStore() {
  const { subscribe, set } = writable({
    user: null,
    token: null,
    isAuthenticated: false,
    loading: true
  });

  return {
    subscribe,
    
    checkAuth: () => {
      if (!browser) return;
      
      const token = localStorage.getItem('bezmidar_token');
      const userStr = localStorage.getItem('bezmidar_user');
      
      if (token && userStr) {
        const user = JSON.parse(userStr);
        set({ user, token, isAuthenticated: true, loading: false });
      } else {
        set({ user: null, token: null, isAuthenticated: false, loading: false });
      }
    },
    
    login: (user, token) => {
      if (browser) {
        localStorage.setItem('bezmidar_token', token);
        localStorage.setItem('bezmidar_user', JSON.stringify(user));
      }
      set({ user, token, isAuthenticated: true, loading: false });
    },
    
    logout: () => {
      if (browser) {
        localStorage.removeItem('bezmidar_token');
        localStorage.removeItem('bezmidar_user');
      }
      set({ user: null, token: null, isAuthenticated: false, loading: false });
      goto('/');
    }
  };
}

export const authStore = createAuthStore();
```

**src/lib/utils/api.js**

```javascript
import { get } from 'svelte/store';
import { authStore } from '$lib/stores/auth.js';

const API_URL = import.meta.env.PUBLIC_API_URL;

class APIClient {
  async request(endpoint, options = {}) {
    const { token } = get(authStore);
    
    const config = {
      ...options,
      headers: {
        'Content-Type': 'application/json',
        ...(token && { Authorization: `Bearer ${token}` }),
        ...options.headers
      }
    };
    
    try {
      const response = await fetch(`${API_URL}${endpoint}`, config);
      const data = await response.json();
      
      if (!response.ok) throw new Error(data.error || 'Request failed');
      return data;
    } catch (error) {
      console.error('API Error:', error);
      throw error;
    }
  }
  
  get(endpoint, params = {}) {
    const query = new URLSearchParams(params).toString();
    return this.request(query ? `${endpoint}?${query}` : endpoint, { method: 'GET' });
  }
  
  post(endpoint, data) {
    return this.request(endpoint, {
      method: 'POST',
      body: JSON.stringify(data)
    });
  }
}

export const api = new APIClient();
```

### Adım 2.2: Layout ve Navigation

**src/routes/+layout.svelte**

```svelte
<script>
  import '../app.css';
  import Navbar from '$lib/components/Navbar.svelte';
  import Footer from '$lib/components/Footer.svelte';
  import { onMount } from 'svelte';
  import { authStore } from '$lib/stores/auth.js';
  
  onMount(() => authStore.checkAuth());
</script>

<div class="min-h-screen flex flex-col">
  <Navbar />
  <main class="flex-1">
    <slot />
  </main>
  <Footer />
</div>
```

**src/lib/components/Navbar.svelte**

```svelte
<script>
  import { authStore } from '$lib/stores/auth.js';
  import { goto } from '$app/navigation';
  
  let mobileMenuOpen = false;
  
  $: user = $authStore.user;
  $: isAuthenticated = $authStore.isAuthenticated;
</script>

<nav class="bg-white shadow-sm sticky top-0 z-50">
  <div class="container mx-auto px-4">
    <div class="flex justify-between items-center h-16">
      <!-- Logo -->
      <a href="/" class="flex items-center gap-2">
        <img src="/logo.svg" alt="Bezmidar" class="h-8" />
        <span class="font-bold text-xl text-blue-600">Bezmidar</span>
      </a>
      
      <!-- Desktop Menu -->
      <div class="hidden md:flex items-center gap-6">
        <a href="/ara" class="text-gray-700 hover:text-blue-600">Öğretmen Ara</a>
        <a href="/hakkimizda" class="text-gray-700 hover:text-blue-600">Hakkımızda</a>
        
        {#if isAuthenticated}
          <a href="/panel" class="text-gray-700 hover:text-blue-600">
            👤 {user?.full_name}
          </a>
          <button 
            on:click={() => authStore.logout()}
            class="text-red-600 hover:text-red-700"
          >
            Çıkış
          </button>
        {:else}
          <a href="/giris" class="text-gray-700 hover:text-blue-600">Giriş</a>
          <a 
            href="/kayit" 
            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700"
          >
            Kayıt Ol
          </a>
        {/if}
      </div>
      
      <!-- Mobile Menu Button -->
      <button 
        class="md:hidden"
        on:click={() => mobileMenuOpen = !mobileMenuOpen}
      >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>
    </div>
  </div>
  
  <!-- Mobile Menu -->
  {#if mobileMenuOpen}
    <div class="md:hidden border-t">
      <div class="container mx-auto px-4 py-4 flex flex-col gap-3">
        <a href="/ara" class="py-2">Öğretmen Ara</a>
        <a href="/hakkimizda" class="py-2">Hakkımızda</a>
        {#if isAuthenticated}
          <a href="/panel" class="py-2">Panelim</a>
          <button on:click={() => authStore.logout()} class="text-left py-2 text-red-600">
            Çıkış
          </button>
        {:else}
          <a href="/giris" class="py-2">Giriş</a>
          <a href="/kayit" class="py-2 text-blue-600">Kayıt Ol</a>
        {/if}
      </div>
    </div>
  {/if}
</nav>
```

### Adım 2.3: Ana Sayfa

**src/routes/+page.svelte**

```svelte
<script>
  import { goto } from '$app/navigation';
</script>

<svelte:head>
  <title>Bezmidar - Gurbet Çocuklarına Özel Ders</title>
  <meta name="description" content="Almanya'daki Türk ailelerini üniversiteli öğretmenlerle buluşturan platform" />
</svelte:head>

<!-- Hero Section -->
<section class="bg-gradient-to-r from-blue-600 to-blue-800 text-white">
  <div class="container mx-auto px-4 py-20">
    <div class="max-w-3xl mx-auto text-center">
      <h1 class="text-4xl md:text-5xl font-bold mb-6">
        Çocuğunuzun dilinden anlayan öğretmenler
      </h1>
      <p class="text-xl mb-8 text-blue-100">
        Almanya'daki Türk ailelerini üniversiteli gençlerle buluşturan eğitim köprüsü
      </p>
      <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <button 
          on:click={() => goto('/ara')}
          class="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-blue-50"
        >
          Öğretmen Ara
        </button>
        <button 
          on:click={() => goto('/kayit?role=student')}
          class="border-2 border-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-700"
        >
          Öğretmen Ol
        </button>
      </div>
    </div>
  </div>
</section>

<!-- Features Grid -->
<section class="py-16 bg-gray-50">
  <div class="container mx-auto px-4">
    <h2 class="text-3xl font-bold text-center mb-12">Neden Bezmidar?</h2>
    
    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
      <div class="bg-white p-6 rounded-lg shadow-sm text-center">
        <div class="text-5xl mb-4">🎓</div>
        <h3 class="text-xl font-semibold mb-2">Güvenilir Öğretmenler</h3>
        <p class="text-gray-600">Üniversite belgesi doğrulanmış, deneyimli öğrenciler</p>
      </div>
      
      <div class="bg-white p-6 rounded-lg shadow-sm text-center">
        <div class="text-5xl mb-4">💰</div>
        <h3 class="text-xl font-semibold mb-2">Uygun Fiyat</h3>
        <p class="text-gray-600">Saatte €15-25 arası esnek ücretlendirme</p>
      </div>
      
      <div class="bg-white p-6 rounded-lg shadow-sm text-center">
        <div class="text-5xl mb-4">🇹🇷</div>
        <h3 class="text-xl font-semibold mb-2">Kültürel Bağ</h3>
        <p class="text-gray-600">Çocuğunuzla aynı dili konuşan rol modeller</p>
      </div>
      
      <div class="bg-white p-6 rounded-lg shadow-sm text-center">
        <div class="text-5xl mb-4">📍</div>
        <h3 class="text-xl font-semibold mb-2">Yakınınızda</h3>
        <p class="text-gray-600">PLZ koduna göre en yakın öğretmenleri bulun</p>
      </div>
    </div>
  </div>
</section>

<!-- How It Works -->
<section class="py-16">
  <div class="container mx-auto px-4">
    <h2 class="text-3xl font-bold text-center mb-12">Nasıl Çalışır?</h2>
    
    <div class="grid md:grid-cols-3 gap-8 max-w-4xl mx-auto">
      {#each [
        { num: 1, title: 'Öğretmen Ara', desc: 'Şehir, ders ve fiyata göre filtrele' },
        { num: 2, title: 'Profil İncele', desc: 'Üniversite, deneyim ve yorumları gör' },
        { num: 3, title: 'İletişime Geç', desc: 'WhatsApp ile direkt mesaj gönder' }
      ] as step}
        <div class="text-center">
          <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl font-bold text-blue-600">
            {step.num}
          </div>
          <h3 class="font-semibold mb-2">{step.title}</h3>
          <p class="text-gray-600">{step.desc}</p>
        </div>
      {/each}
    </div>
  </div>
</section>
```

### Adım 2.4: Arama/Liste Sayfası

**src/routes/ara/+page.svelte**

```svelte
<script>
  import { onMount } from 'svelte';
  import { api } from '$lib/utils/api.js';
  import TeacherCard from '$lib/components/TeacherCard.svelte';
  import FilterSidebar from '$lib/components/FilterSidebar.svelte';
  
  let teachers = [];
  let subjects = [];
  let loading = true;
  let error = null;
  
  // Filter state
  let filters = {
    city: '',
    subject: '',
    max_rate: null,
    page: 1
  };
  
  async function loadSubjects() {
    try {
      const response = await api.get('/subjects/list.php');
      subjects = response.data;
    } catch (e) {
      console.error('Failed to load subjects:', e);
    }
  }
  
  async function loadTeachers() {
    loading = true;
    error = null;
    
    try {
      const response = await api.get('/teachers/list.php', filters);
      teachers = response.data.teachers;
    } catch (e) {
      error = 'Öğretmenler yüklenemedi. Lütfen tekrar deneyin.';
    } finally {
      loading = false;
    }
  }
  
  function applyFilters(newFilters) {
    filters = { ...filters, ...newFilters, page: 1 };
    loadTeachers();
  }
  
  onMount(() => {
    loadSubjects();
    loadTeachers();
  });
</script>

<svelte:head>
  <title>Öğretmen Ara - Bezmidar</title>
</svelte:head>

<div class="container mx-auto px-4 py-8">
  <h1 class="text-3xl font-bold mb-8">Öğretmen Ara</h1>
  
  <div class="grid lg:grid-cols-4 gap-8">
    <!-- Sidebar -->
    <aside class="lg:col-span-1">
      <FilterSidebar {subjects} {filters} on:filter={e => applyFilters(e.detail)} />
    </aside>
    
    <!-- Results -->
    <main class="lg:col-span-3">
      {#if loading}
        <div class="text-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <p class="mt-4 text-gray-600">Yükleniyor...</p>
        </div>
      {:else if error}
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-700">
          {error}
        </div>
      {:else if teachers.length === 0}
        <div class="text-center py-12">
          <p class="text-xl text-gray-600">Henüz öğretmen bulunamadı.</p>
          <p class="text-gray-500 mt-2">Filtreleri değiştirip tekrar deneyin.</p>
        </div>
      {:else}
        <div class="grid md:grid-cols-2 gap-6">
          {#each teachers as teacher}
            <TeacherCard {teacher} />
          {/each}
        </div>
      {/if}
    </main>
  </div>
</div>
```

**src/lib/components/TeacherCard.svelte**

```svelte
<script>
  export let teacher;
  
  const subjects = teacher.subjects ? teacher.subjects.split(',') : [];
</script>

<a 
  href="/profil/{teacher.id}" 
  class="block bg-white rounded-lg shadow-sm hover:shadow-md transition p-4"
>
  <div class="flex gap-4">
    <!-- Avatar -->
    <div class="flex-shrink-0">
      {#if teacher.avatar_url}
        <img 
          src={teacher.avatar_url} 
          alt={teacher.full_name}
          class="w-20 h-20 rounded-full object-cover"
        />
      {:else}
        <div class="w-20 h-20 rounded-full bg-blue-100 flex items-center justify-center text-2xl">
          {teacher.full_name.charAt(0)}
        </div>
      {/if}
    </div>
    
    <!-- Info -->
    <div class="flex-1 min-w-0">
      <div class="flex items-start justify-between">
        <div>
          <h3 class="font-semibold text-lg flex items-center gap-2">
            {teacher.full_name}
            {#if teacher.is_verified}
              <span class="text-blue-600" title="Doğrulanmış">✓</span>
            {/if}
          </h3>
          <p class="text-sm text-gray-600">{teacher.university}</p>
          <p class="text-sm text-gray-500">{teacher.department}</p>
        </div>
        <div class="text-right">
          <p class="text-lg font-bold text-blue-600">€{teacher.hourly_rate}</p>
          <p class="text-xs text-gray-500">/saat</p>
        </div>
      </div>
      
      <!-- Rating -->
      {#if teacher.review_count > 0}
        <div class="flex items-center gap-1 mt-2">
          <span class="text-yellow-500">⭐</span>
          <span class="text-sm font-semibold">{teacher.rating_avg}</span>
          <span class="text-xs text-gray-500">({teacher.review_count} yorum)</span>
        </div>
      {/if}
      
      <!-- Subjects -->
      <div class="flex flex-wrap gap-1 mt-2">
        {#each subjects.slice(0, 3) as subject}
          <span class="px-2 py-1 bg-blue-50 text-blue-700 text-xs rounded">
            {subject}
          </span>
        {/each}
        {#if subjects.length > 3}
          <span class="px-2 py-1 text-xs text-gray-500">
            +{subjects.length - 3} daha
          </span>
        {/if}
      </div>
      
      <!-- Location -->
      <p class="text-sm text-gray-500 mt-2">
        📍 {teacher.city || 'Belirtilmemiş'}
      </p>
    </div>
  </div>
</a>
```

### Adım 2.5: Profil Detay Sayfası

**src/routes/profil/[id]/+page.svelte**

```svelte
<script>
  import { onMount } from 'svelte';
  import { page } from '$app/stores';
  import { authStore } from '$lib/stores/auth.js';
  import { api } from '$lib/utils/api.js';
  
  let teacher = null;
  let loading = true;
  let error = null;
  let showContactModal = false;
  
  $: teacherId = $page.params.id;
  $: isAuthenticated = $authStore.isAuthenticated;
  $: userRole = $authStore.user?.role;
  
  async function loadTeacher() {
    loading = true;
    try {
      const response = await api.get('/teachers/detail.php', { id: teacherId });
      teacher = response.data;
    } catch (e) {
      error = 'Profil yüklenemedi';
    } finally {
      loading = false;
    }
  }
  
  function handleContact() {
    if (!isAuthenticated) {
      alert('İletişim için giriş yapmalısınız');
      window.location.href = '/giris';
      return;
    }
    
    if (userRole !== 'parent') {
      alert('Sadece veliler iletişime geçebilir');
      return;
    }
    
    showContactModal = true;
  }
  
  onMount(loadTeacher);
</script>

<svelte:head>
  <title>{teacher?.full_name || 'Öğretmen Profil'} - Bezmidar</title>
</svelte:head>

{#if loading}
  <div class="container mx-auto px-4 py-12 text-center">
    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
  </div>
{:else if error}
  <div class="container mx-auto px-4 py-12">
    <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-700">
      {error}
    </div>
  </div>
{:else if teacher}
  <div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
      <div class="flex flex-col md:flex-row gap-6">
        <!-- Avatar -->
        {#if teacher.avatar_url}
          <img 
            src={teacher.avatar_url} 
            alt={teacher.full_name}
            class="w-32 h-32 rounded-full object-cover"
          />
        {:else}
          <div class="w-32 h-32 rounded-full bg-blue-100 flex items-center justify-center text-4xl">
            {teacher.full_name.charAt(0)}
          </div>
        {/if}
        
        <!-- Info -->
        <div class="flex-1">
          <h1 class="text-3xl font-bold mb-2 flex items-center gap-2">
            {teacher.full_name}
            {#if teacher.is_verified}
              <span class="text-blue-600 text-xl" title="Doğrulanmış öğretmen">✓</span>
            {/if}
          </h1>
          
          <div class="space-y-1 text-gray-600 mb-4">
            <p>🎓 {teacher.university} - {teacher.department}</p>
            {#if teacher.graduation_year}
              <p>📅 Mezuniyet: {teacher.graduation_year}</p>
            {/if}
            <p>📍 {teacher.city}, PLZ: {teacher.zip_code}</p>
            <p>⏱️ {teacher.experience_years} yıl deneyim</p>
          </div>
          
          <!-- Rating -->
          {#if teacher.review_count > 0}
            <div class="flex items-center gap-2 mb-4">
              <span class="text-2xl">⭐</span>
              <span class="text-xl font-bold">{teacher.rating_avg}</span>
              <span class="text-gray-500">({teacher.review_count} değerlendirme)</span>
            </div>
          {/if}
          
          <!-- Price & Contact -->
          <div class="flex items-center gap-4">
            <div class="text-3xl font-bold text-blue-600">
              €{teacher.hourly_rate}<span class="text-lg text-gray-500">/saat</span>
            </div>
            <button 
              on:click={handleContact}
              class="bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 flex items-center gap-2"
            >
              <span>💬</span>
              WhatsApp ile İletişime Geç
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Bio -->
    {#if teacher.bio}
      <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h2 class="text-xl font-bold mb-3">Hakkımda</h2>
        <p class="text-gray-700 whitespace-pre-wrap">{teacher.bio}</p>
      </div>
    {/if}
    
    <!-- Subjects -->
    {#if teacher.subjects && teacher.subjects.length > 0}
      <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h2 class="text-xl font-bold mb-3">Verdiğim Dersler</h2>
        <div class="flex flex-wrap gap-2">
          {#each teacher.subjects as subject}
            <div class="px-4 py-2 bg-blue-50 text-blue-700 rounded-lg flex items-center gap-2">
              <span class="text-xl">{subject.icon}</span>
              <span class="font-semibold">{subject.name}</span>
              <span class="text-sm text-gray-500">({subject.proficiency_level})</span>
            </div>
          {/each}
        </div>
      </div>
    {/if}
    
    <!-- Reviews -->
    {#if teacher.reviews && teacher.reviews.length > 0}
      <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-xl font-bold mb-4">Değerlendirmeler</h2>
        <div class="space-y-4">
          {#each teacher.reviews as review}
            <div class="border-b pb-4 last:border-0">
              <div class="flex items-center gap-2 mb-2">
                <div class="flex">
                  {#each Array(review.rating) as _}
                    <span class="text-yellow-500">⭐</span>
                  {/each}
                </div>
                <span class="font-semibold">{review.parent_name}</span>
                <span class="text-gray-500 text-sm">
                  {new Date(review.created_at).toLocaleDateString('tr-TR')}
                </span>
              </div>
              <p class="text-gray-700">{review.comment}</p>
            </div>
          {/each}
        </div>
      </div>
    {/if}
  </div>
{/if}

<!-- Contact Modal -->
{#if showContactModal}
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg max-w-md w-full p-6">
      <h3 class="text-xl font-bold mb-4">İletişim Bilgileri</h3>
      <p class="mb-4">WhatsApp ile iletişime geçmek için aşağıdaki numaraya mesaj gönderin:</p>
      <a 
        href="https://wa.me/{teacher.phone}?text=Merhaba, Bezmidar'dan proflinizi gördüm."
        target="_blank"
        class="block w-full bg-green-600 text-white text-center py-3 rounded-lg font-semibold hover:bg-green-700 mb-4"
      >
        WhatsApp'ta Aç
      </a>
      <button 
        on:click={() => showContactModal = false}
        class="w-full text-gray-600 py-2"
      >
        Kapat
      </button>
    </div>
  </div>
{/if}
```

---

## HAFTA 5: Mobil (Capacitor) Entegrasyonu

### Adım 3.1: Capacitor Platformları Ekle

```bash
# Android ekle
npx cap add android

# iOS ekle (sadece Mac'te)
npx cap add ios

# Build ve sync
npm run build
npx cap sync
```

### Adım 3.2: Android Studio'da Test

```bash
# Android Studio'da aç
npx cap open android

# Veya doğrudan çalıştır
npx cap run android
```

### Adım 3.3: Mobil için Özel Düzenlemeler

**Android Manifest** (`android/app/src/main/AndroidManifest.xml`):

```xml
<manifest ...>
    <uses-permission android:name="android.permission.INTERNET" />
    
    <application
        android:allowBackup="true"
        android:icon="@mipmap/ic_launcher"
        android:label="@string/app_name"
        android:roundIcon="@mipmap/ic_launcher_round"
        android:supportsRtl="true"
        android:usesCleartextTraffic="true">
        <!-- Dev için cleartext, prod'da kaldır -->
    </application>
</manifest>
```

### Adım 3.4: Safe Area Handling

**src/app.css** içine ekle:

```css
/* Safe area padding for mobile notch */
.safe-top {
  padding-top: env(safe-area-inset-top);
}

.safe-bottom {
  padding-bottom: env(safe-area-inset-bottom);
}

/* Navbar için */
nav {
  padding-top: max(1rem, env(safe-area-inset-top));
}
```

---

## HAFTA 6: Deployment ve Launch

### Adım 4.1: Production Build

```bash
# Environment variables production'a çek
cp .env.example .env
# .env dosyasını production URL'leriyle düzenle

# Build
npm run build

# Build klasörünü kontrol et
ls -la build/
```

### Adım 4.2: Shared Hosting'e Upload

**FTP ile:**
1. `build/` klasörünün tüm içeriğini `/public_html/` altına kopyala
2. `server/` klasörünü root'a yükle

**SSH ile (daha hızlı):**

```bash
# Yerel makineden
rsync -avz build/ user@server:/home/user/public_html/
rsync -avz server/api/ user@server:/home/user/api/
```

### Adım 4.3: .htaccess Ayarları

**public_html/.htaccess**

```apache
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /
  
  # API isteklerini /api klasörüne yönlendir
  RewriteCond %{REQUEST_URI} ^/api/
  RewriteRule ^api/(.*)$ /api/$1 [L]
  
  # Static dosyalar varsa, direkt sun
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  
  # Yoksa index.html'e yönlendir (SPA mode)
  RewriteRule . /index.html [L]
</IfModule>

# Security headers
<IfModule mod_headers.c>
  Header set X-Content-Type-Options "nosniff"
  Header set X-Frame-Options "SAMEORIGIN"
  Header set X-XSS-Protection "1; mode=block"
</IfModule>

# Gzip compression
<IfModule mod_deflate.c>
  AddOutputFilterByType DEFLATE text/html text/css text/javascript application/javascript application/json
</IfModule>

# Cache static assets
<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresByType image/jpg "access plus 1 year"
  ExpiresByType image/jpeg "access plus 1 year"
  ExpiresByType image/gif "access plus 1 year"
  ExpiresByType image/png "access plus 1 year"
  ExpiresByType image/webp "access plus 1 year"
  ExpiresByType text/css "access plus 1 month"
  ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

### Adım 4.4: Database Kurulum (cPanel)

1. cPanel → MySQL Databases
2. Yeni database oluştur: `username_bezmidar`
3. Yeni user oluştur ve şifre ver
4. User'ı database'e ekle (ALL PRIVILEGES)
5. phpMyAdmin → SQL sekmesi → `schema.sql` dosyasını çalıştır

### Adım 4.5: Environment Variables (Sunucu)

**server/.env** oluştur:

```bash
DB_HOST=localhost
DB_NAME=username_bezmidar
DB_USER=username_bezmidar_user
DB_PASS=güçlü_şifre_buraya
JWT_SECRET=32_karakterli_random_string_buraya
```

**PHP dosyalarında okuma:**

```php
// Her config/db.php'de
if (file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    foreach ($env as $key => $value) {
        putenv("$key=$value");
    }
}
```

---

## 🔧 Development Workflow

### Günlük Geliştirme

```bash
# Frontend dev server
npm run dev

# Backend test (PHP built-in server)
cd server
php -S localhost:8000

# Her ikisini aynı anda (package.json'a ekle)
npm run dev:all
```

**package.json script'leri:**

```json
{
  "scripts": {
    "dev": "vite dev",
    "build": "vite build",
    "preview": "vite preview",
    "dev:api": "cd server && php -S localhost:8000",
    "sync": "npm run build && npx cap sync",
    "android": "npm run sync && npx cap run android"
  }
}
```

### Git Workflow

```bash
# İlk commit
git add .
git commit -m "Initial commit: MVP structure"

# Feature branches
git checkout -b feature/teacher-profile
# ... geliştir ...
git commit -m "Add: Teacher profile detail page"
git checkout main
git merge feature/teacher-profile

# Deploy tag
git tag -a v1.0.0 -m "MVP Launch"
git push origin main --tags
```

---

## 🐛 Debug ve Troubleshooting

### Frontend Debug

**Browser DevTools:**
- Network tab → API isteklerini izle
- Console → JavaScript hataları
- Application → LocalStorage kontrol

**SvelteKit Debug:**

```javascript
// src/hooks.server.js (opsiyonel)
export async function handle({ event, resolve }) {
  console.log('Request:', event.url.pathname);
  const response = await resolve(event);
  console.log('Response:', response.status);
  return response;
}
```

### Backend Debug

**PHP Error Logging:**

```php
// api/config/debug.php (sadece dev'de kullan)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Hataları loglama
error_log("Debug: " . print_r($data, true), 3, "/path/to/error.log");
```

### Mobil Debug

**Android:**

```bash
# Logcat izle
adb logcat | grep -i chromium

# Chrome DevTools
# Chrome'da chrome://inspect aç
```

**iOS (Safari):**
- Safari → Develop → [Device Name] → [App]

---

## 📊 Performance Optimization

### Image Optimization

```bash
# WebP dönüştürme (öğretmen fotoğrafları için)
npm install sharp

# Script: optimize-images.js
const sharp = require('sharp');
sharp('avatar.jpg')
  .resize(200, 200)
  .webp({ quality: 80 })
  .toFile('avatar.webp');
```

### Lazy Loading

```svelte
<!-- TeacherCard.svelte -->
<img 
  src={teacher.avatar_url} 
  alt={teacher.full_name}
  loading="lazy"
  class="w-20 h-20 rounded-full"
/>
```

### API Response Caching

```javascript
// src/lib/utils/cache.js
const cache = new Map();
const CACHE_DURATION = 5 * 60 * 1000; // 5 dakika

export function cachedFetch(key, fetcher) {
  const cached = cache.get(key);
  if (cached && Date.now() - cached.timestamp < CACHE_DURATION) {
    return Promise.resolve(cached.data);
  }
  
  return fetcher().then(data => {
    cache.set(key, { data, timestamp: Date.now() });
    return data;
  });
}
```

---

## ✅ Launch Checklist

### Teknik Checklist

- [ ] Database prod'da kurulu ve seed data çalıştırıldı
- [ ] Tüm API endpoint'ler test edildi
- [ ] CORS ayarları doğru
- [ ] JWT secret production value'su
- [ ] .htaccess SPA rewrite çalışıyor
- [ ] SSL sertifikası aktif (HTTPS)
- [ ] robots.txt eklendi
- [ ] sitemap.xml oluşturuldu
- [ ] Google Analytics/Plausible kuruldu
- [ ] Error monitoring (Sentry optional)
- [ ] Backup stratejisi hazır

### UX Checklist

- [ ] Tüm formlar validasyon yapıyor
- [ ] Loading state'ler var
- [ ] Error message'lar kullanıcı dostu
- [ ] Mobil responsive tüm sayfalar
- [ ] Touch target'lar yeterince büyük (44px+)
- [ ] Keyboard navigation çalışıyor
- [ ] Alt text'ler tüm görsellerde

### Güvenlik Checklist

- [ ] SQL injection korumalı (PDO prepared statements)
- [ ] XSS korumalı (htmlspecialchars)
- [ ] CSRF token sistemi (opsiyonel v2)
- [ ] Rate limiting (opsiyonel v2)
- [ ] File upload validation
- [ ] Password minimum 8 karakter
- [ ] Sensitive data .env'de

---

## 📱 Mobil Deploy (Google Play)

### Android APK Build

```bash
# Build release APK
npm run build
npx cap sync android
cd android
./gradlew assembleRelease

# APK lokasyonu:
# android/app/build/outputs/apk/release/app-release-unsigned.apk
```

### Signing (Yayın için)

```bash
# Keystore oluştur (bir kez)
keytool -genkey -v -keystore bezmidar-release.keystore -alias bezmidar -keyalg RSA -keysize 2048 -validity 10000

# APK imzala
jarsigner -verbose -sigalg SHA1withRSA -digestalg SHA1 -keystore bezmidar-release.keystore app-release-unsigned.apk bezmidar

# Zipalign
zipalign -v 4 app-release-unsigned.apk bezmidar.apk
```

### Google Play Console

1. Developer hesabı aç (€25 one-time fee)
2. App oluştur
3. Store listing doldur (screenshots, açıklama)
4. APK yükle → Internal testing'e al
5. Test ettikten sonra Production'a yayınla

---

## 🎯 V2 Feature Roadmap (3-6 Ay Sonra)

### Yüksek Öncelik

- [ ] İçerik mesajlaşma sistemi (Firebase/Pusher)
- [ ] Push notification (Öğretmen yeni talep aldığında)
- [ ] Email bildirimleri (Transactional)
- [ ] Ödeme entegrasyonu (Stripe)
- [ ] Profil doğrulama otomasyonu

### Orta Öncelik

- [ ] Takvim/randevu sistemi
- [ ] Video görüşme (Jitsi/Daily.co)
- [ ] Blog/SEO content sistemi
- [ ] Referral program
- [ ] Admin panel (user management)

### Düşük Öncelik

- [ ] iOS app (SwiftUI native veya Capacitor)
- [ ] AI önerme sistemi (ML matching)
- [ ] Multi-language (Almanca)
- [ ] Dark mode
- [ ] Progressive Web App (PWA) optimization

---

## 📚 Faydalı Komutlar Özeti

```bash
# Development
npm run dev                    # Frontend dev server
php -S localhost:8000          # Backend test server

# Build
npm run build                  # Production build
npx cap sync                   # Mobil sync

# Deployment
rsync -avz build/ user@server:/public_html/
ssh user@server "cd api && composer install --no-dev"

# Git
git status
git add .
git commit -m "message"
git push origin main

# Mobil
npx cap open android           # Android Studio aç
npx cap run android            # Direkt çalıştır
adb logcat                     # Android log

# Database
mysql -u user -p database < schema.sql    # Schema import
mysqldump -u user -p database > backup.sql # Backup
```

---

## 🆘 Destek ve Kaynaklar

### Dokümantasyon

- SvelteKit: https://kit.svelte.dev/docs
- Capacitor: https://capacitorjs.com/docs
- TailwindCSS: https://tailwindcss.com/docs
- PHP PDO: https://www.php.net/manual/en/book.pdo.php

### Community

- SvelteKit Discord: https://svelte.dev/chat
- Stack Overflow: `[sveltekit]` tag
- PHP: https://www.reddit.com/r/PHP/

---

## 🎉 Son Notlar

Bu roadmap MVP için gereken tüm teknik detayları kapsıyor. Her adımda:

1. **Test et** - Her özelliği ekledikten sonra test et
2. **Commit et** - Küçük, anlamlı commit'ler yap
3. **Deploy et** - Erken ve sık deploy et (staging environment)

**Başarı için temel prensip:** "Make it work, make it right, make it fast"

Önce çalışır hale getir (MVP), sonra iyileştir (V2), en son optimize et (V3).

İyi çalışmalar! 🚀
