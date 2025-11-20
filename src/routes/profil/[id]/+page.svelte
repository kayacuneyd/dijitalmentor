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
    <div class="grid lg:grid-cols-3 gap-8">
      
      <!-- Left Column (Main Content) -->
      <div class="lg:col-span-2 space-y-6">
        
        <!-- Header Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
          <div class="flex flex-col sm:flex-row gap-6 items-start">
            <!-- Avatar -->
            {#if teacher.avatar_url}
              <img 
                src={teacher.avatar_url} 
                alt={teacher.full_name}
                class="w-32 h-32 rounded-full object-cover border-4 border-blue-50"
              />
            {:else}
              <div class="w-32 h-32 rounded-full bg-blue-100 flex items-center justify-center text-4xl text-blue-600 font-bold">
                {teacher.full_name.charAt(0)}
              </div>
            {/if}
            
            <!-- Info -->
            <div class="flex-1">
              <h1 class="text-3xl font-bold mb-2 flex items-center gap-2 text-gray-900">
                {teacher.full_name}
                {#if teacher.is_verified}
                  <span class="text-blue-600 text-xl" title="Doğrulanmış öğretmen">✓</span>
                {/if}
              </h1>
              
              <div class="space-y-2 text-gray-600 mb-4">
                <p class="flex items-center gap-2">
                  <span>🎓</span>
                  <span>{teacher.university} - {teacher.department}</span>
                </p>
                {#if teacher.graduation_year}
                  <p class="flex items-center gap-2">
                    <span>📅</span>
                    <span>Mezuniyet: {teacher.graduation_year}</span>
                  </p>
                {/if}
                <p class="flex items-center gap-2">
                  <span>📍</span>
                  <span>{teacher.city}, PLZ: {teacher.zip_code}</span>
                </p>
                <p class="flex items-center gap-2">
                  <span>⏱️</span>
                  <span>{teacher.experience_years} yıl deneyim</span>
                </p>
              </div>
              
              <!-- Rating -->
              {#if teacher.review_count > 0}
                <div class="flex items-center gap-2">
                  <span class="text-yellow-400 text-xl">⭐</span>
                  <span class="text-xl font-bold text-gray-900">{teacher.rating_avg}</span>
                  <span class="text-gray-500 text-sm">({teacher.review_count} değerlendirme)</span>
                </div>
              {/if}
            </div>
          </div>
        </div>
        
        <!-- Bio -->
        {#if teacher.bio}
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-xl font-bold mb-4 text-gray-900">Hakkımda</h2>
            <p class="text-gray-700 whitespace-pre-wrap leading-relaxed">{teacher.bio}</p>
          </div>
        {/if}
        
        <!-- Subjects -->
        {#if teacher.subjects && teacher.subjects.length > 0}
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-xl font-bold mb-4 text-gray-900">Verdiğim Dersler</h2>
            <div class="grid sm:grid-cols-2 gap-4">
              {#each teacher.subjects as subject}
                <div class="p-4 bg-gray-50 rounded-lg flex items-center gap-3 border border-gray-100">
                  <span class="text-2xl">{subject.icon}</span>
                  <div>
                    <div class="font-semibold text-gray-900">{subject.name}</div>
                    <div class="text-sm text-gray-500">{subject.proficiency_level}</div>
                  </div>
                </div>
              {/each}
            </div>
          </div>
        {/if}
        
        <!-- Reviews -->
        {#if teacher.reviews && teacher.reviews.length > 0}
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-xl font-bold mb-6 text-gray-900">Değerlendirmeler</h2>
            <div class="space-y-6">
              {#each teacher.reviews as review}
                <div class="border-b border-gray-100 pb-6 last:border-0 last:pb-0">
                  <div class="flex justify-between items-start mb-2">
                    <div class="flex items-center gap-2">
                      <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-bold text-gray-600">
                        {review.parent_name.charAt(0)}
                      </div>
                      <span class="font-semibold text-gray-900">{review.parent_name}</span>
                    </div>
                    <span class="text-gray-500 text-sm">
                      {new Date(review.created_at).toLocaleDateString('tr-TR')}
                    </span>
                  </div>
                  <div class="flex text-yellow-400 text-sm mb-2">
                    {#each Array(review.rating) as _}
                      <span>⭐</span>
                    {/each}
                  </div>
                  <p class="text-gray-700">{review.comment}</p>
                </div>
              {/each}
            </div>
          </div>
        {/if}
      </div>
      
      <!-- Right Column (Sidebar) -->
      <div class="space-y-6">
        
        <!-- Action Card (Sticky on Desktop) -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 sticky top-4">
          <div class="text-center mb-6">
            <div class="text-sm text-gray-500 mb-1">Saatlik Ücret</div>
            <div class="text-4xl font-bold text-blue-600">
              €{teacher.hourly_rate}
            </div>
          </div>
          
          <button 
            on:click={handleContact}
            class="w-full bg-green-600 text-white py-4 rounded-xl font-bold hover:bg-green-700 transition flex items-center justify-center gap-2 shadow-lg shadow-green-100"
          >
            <span>💬</span>
            WhatsApp ile İletişime Geç
          </button>
          
          <p class="text-xs text-center text-gray-400 mt-4">
            Güvenliğiniz için tüm görüşmeleri platform üzerinden başlatın.
          </p>
        </div>

        <!-- Video Intro -->
        {#if teacher.video_intro_url}
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-4 border-b border-gray-100">
              <h3 class="font-bold text-gray-900 flex items-center gap-2">
                <span>🎥</span> Tanıtım Videosu
              </h3>
            </div>
            <div class="aspect-video bg-black">
              <iframe 
                width="100%" 
                height="100%" 
                src={teacher.video_intro_url} 
                title="Teacher Video"
                frameborder="0" 
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                allowfullscreen
              ></iframe>
            </div>
          </div>
        {/if}
        
      </div>
      
    </div>
  </div>
{/if}

<!-- Contact Modal -->
{#if showContactModal}
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 backdrop-blur-sm">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl transform transition-all">
      <div class="text-center mb-6">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl">
          💬
        </div>
        <h3 class="text-xl font-bold text-gray-900">İletişim Bilgileri</h3>
        <p class="text-gray-500 mt-2">WhatsApp üzerinden öğretmenle doğrudan iletişime geçebilirsiniz.</p>
      </div>
      
      <a 
        href="https://wa.me/{teacher.phone}?text=Merhaba, Bezmidar'dan proflinizi gördüm."
        target="_blank"
        class="block w-full bg-green-600 text-white text-center py-4 rounded-xl font-bold hover:bg-green-700 transition mb-4 shadow-lg shadow-green-200"
      >
        WhatsApp'ta Sohbet Başlat
      </a>
      
      <button 
        on:click={() => showContactModal = false}
        class="w-full text-gray-500 font-medium py-3 hover:bg-gray-50 rounded-xl transition"
      >
        Kapat
      </button>
    </div>
  </div>
{/if}
