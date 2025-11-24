<script>
  import { authStore } from '$lib/stores/auth.js';
  import Button from '$lib/components/Button.svelte';
  
  let mobileMenuOpen = false;
  let aboutDropdownOpen = false;
  let mobileAboutOpen = false;

  function closeMobileMenu() {
    mobileMenuOpen = false;
    mobileAboutOpen = false;
  }
  
  function toggleMobileAbout() {
    mobileAboutOpen = !mobileAboutOpen;
  }
  
  $: user = $authStore.user;
  $: isAuthenticated = $authStore.isAuthenticated;
</script>

<nav class="bg-white shadow-sm sticky top-0 z-50 border-b border-gray-100">
  <div class="container mx-auto px-4">
    <div class="flex justify-between items-center h-16">
      <!-- Logo -->
      <a href="/" class="flex items-center gap-2">
        <span class="font-bold text-xl text-primary-600">DijitalMentor</span>
      </a>
      
      <!-- Desktop Menu -->
      <div class="hidden md:flex items-center gap-6">
        {#if user?.role === 'parent'}
          <a href="/ara" class="text-gray-700 hover:text-primary-600 font-medium transition">Öğretmen Bul</a>
        {:else if user?.role === 'student'}
          <a href="/ders-talepleri" class="text-gray-700 hover:text-primary-600 font-medium transition">Ders Talepleri</a>
        {:else}
          <a href="/ara" class="text-gray-700 hover:text-primary-600 font-medium transition">Öğretmen Bul</a>
          <a href="/ders-talepleri" class="text-gray-700 hover:text-primary-600 font-medium transition">Ders Talepleri</a>
        {/if}

        <!-- Dropdown Menu -->
        <div class="relative group"
             on:mouseenter={() => aboutDropdownOpen = true}
             on:mouseleave={() => aboutDropdownOpen = false}>
          <button class="flex items-center gap-1 text-gray-700 hover:text-primary-600 font-medium transition py-2">
            Hakkımızda
            <svg class="w-4 h-4 transform transition-transform duration-200" class:rotate-180={aboutDropdownOpen} fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
          
          <div class="absolute top-full left-0 w-48 bg-white shadow-xl rounded-xl border border-gray-100 overflow-hidden transition-all duration-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible translate-y-2 group-hover:translate-y-0">
             <a href="/hakkimizda" class="block px-4 py-3 hover:bg-gray-50 text-gray-700 hover:text-primary-600 transition border-b border-gray-50">
               Kurumsal
             </a>
             <a href="/podcast" class="block px-4 py-3 hover:bg-gray-50 text-gray-700 hover:text-primary-600 transition border-b border-gray-50">
               Podcast
             </a>
             <a href="/blog" class="block px-4 py-3 hover:bg-gray-50 text-gray-700 hover:text-primary-600 transition border-b border-gray-50">
               Blog
             </a>
             <a href="/nasil-calisir" class="block px-4 py-3 hover:bg-gray-50 text-gray-700 hover:text-primary-600 transition border-b border-gray-50">
               Nasıl Çalışır?
             </a>
             <a href="/danisma-kurulu" class="block px-4 py-3 hover:bg-gray-50 text-gray-700 hover:text-primary-600 transition">
               Danışma Kurulu
             </a>
          </div>
        </div>

        <a href="/iletisim" class="text-gray-700 hover:text-primary-600 font-medium transition">İletişim</a>
        
        {#if isAuthenticated}
          <a href="/panel" class="text-gray-700 hover:text-primary-600 transition">
            👤 {user?.full_name}
          </a>
          <button 
            on:click={() => authStore.logout()}
            class="text-error hover:text-error-600 font-medium transition"
          >
            Çıkış
          </button>
        {:else}
          <a href="/giris" class="text-gray-700 hover:text-primary-600 font-medium transition">Giriş</a>
          <Button variant="primary" size="sm" href="/kayit">
            Kayıt Ol
          </Button>
        {/if}
      </div>
      
      <!-- Mobile Menu Button -->
      <button 
        aria-label="Menüyü aç/kapat"
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
        {#if user?.role === 'parent'}
          <a href="/ara" class="py-2" on:click={closeMobileMenu}>Öğretmen Bul</a>
        {:else if user?.role === 'student'}
          <a href="/ders-talepleri" class="py-2" on:click={closeMobileMenu}>Ders Talepleri</a>
        {:else}
          <a href="/ara" class="py-2" on:click={closeMobileMenu}>Öğretmen Bul</a>
          <a href="/ders-talepleri" class="py-2" on:click={closeMobileMenu}>Ders Talepleri</a>
        {/if}
        
        <!-- Mobile Dropdown -->
        <div class="py-2">
          <button 
            on:click={toggleMobileAbout}
            class="flex items-center justify-between w-full text-left"
          >
            <span class="font-medium text-gray-900">Hakkımızda</span>
            <svg class="w-4 h-4 transform transition-transform duration-200 {mobileAboutOpen ? 'rotate-180' : ''}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
          
          {#if mobileAboutOpen}
            <div class="pl-4 mt-2 space-y-2 border-l-2 border-gray-100 ml-1">
              <a href="/hakkimizda" class="block py-1 text-gray-600" on:click={closeMobileMenu}>Kurumsal</a>
              <a href="/podcast" class="block py-1 text-gray-600" on:click={closeMobileMenu}>Podcast</a>
              <a href="/blog" class="block py-1 text-gray-600" on:click={closeMobileMenu}>Blog</a>
              <a href="/nasil-calisir" class="block py-1 text-gray-600" on:click={closeMobileMenu}>Nasıl Çalışır?</a>
              <a href="/danisma-kurulu" class="block py-1 text-gray-600" on:click={closeMobileMenu}>Danışma Kurulu</a>
            </div>
          {/if}
        </div>

        <a href="/iletisim" class="py-2" on:click={closeMobileMenu}>İletişim</a>
        
        {#if isAuthenticated}
          <a href="/panel" class="py-2" on:click={closeMobileMenu}>Panelim</a>
          <button on:click={() => { authStore.logout(); closeMobileMenu(); }} class="text-left py-2 text-red-600">
            Çıkış
          </button>
        {:else}
          <a href="/giris" class="py-2" on:click={closeMobileMenu}>Giriş</a>
          <a href="/kayit" class="py-2 text-blue-600" on:click={closeMobileMenu}>Kayıt Ol</a>
        {/if}
      </div>
    </div>
  {/if}
</nav>
