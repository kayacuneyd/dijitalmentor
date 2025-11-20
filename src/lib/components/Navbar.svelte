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
        <a href="/ara" class="text-gray-600 hover:text-blue-600 font-medium">Öğretmen Bul</a>
        <a href="/ders-talepleri" class="text-gray-600 hover:text-blue-600 font-medium">Ders Talepleri</a>
        <a href="/nasil-calisir" class="text-gray-600 hover:text-blue-600 font-medium">Nasıl Çalışır?</a>
        
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
