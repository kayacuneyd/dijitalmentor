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
