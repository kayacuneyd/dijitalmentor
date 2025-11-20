<script>
  import { onMount } from 'svelte';
  import { api } from '$lib/utils/api.js';
  import { authStore } from '$lib/stores/auth.js';
  import { goto } from '$app/navigation';

  let requests = [];
  let loading = true;

  onMount(async () => {
    if (!$authStore.isAuthenticated) {
      goto('/giris');
      return;
    }
    
    try {
      const response = await api.get('/requests/my_requests.php');
      requests = response.data || [];
    } catch (e) {
      console.error(e);
    } finally {
      loading = false;
    }
  });
</script>

<svelte:head>
  <title>Taleplerim - Bezmidar</title>
</svelte:head>

<div class="container mx-auto px-4 py-8">
  <div class="flex justify-between items-center mb-8">
    <h1 class="text-2xl font-bold">Ders Taleplerim</h1>
    <a href="/panel/taleplerim/yeni" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
      + Yeni Talep Oluştur
    </a>
  </div>

  {#if loading}
    <div class="text-center py-8">Yükleniyor...</div>
  {:else if requests.length === 0}
    <div class="bg-white p-8 rounded-lg shadow-sm text-center border border-gray-100">
      <div class="text-4xl mb-4">📝</div>
      <h3 class="text-lg font-semibold mb-2">Henüz talep oluşturmadınız</h3>
      <p class="text-gray-600 mb-6">Aradığınız özel ders için hemen bir ilan oluşturun.</p>
      <a href="/panel/taleplerim/yeni" class="text-blue-600 font-semibold hover:underline">
        İlk talebini oluştur
      </a>
    </div>
  {:else}
    <div class="space-y-4">
      {#each requests as request}
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 flex justify-between items-center">
          <div>
            <div class="flex items-center gap-2 mb-1">
              <h3 class="font-bold text-lg">{request.title}</h3>
              <span class="text-xs px-2 py-0.5 rounded-full {request.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'}">
                {request.status === 'active' ? 'Yayında' : 'Kapalı'}
              </span>
            </div>
            <p class="text-gray-600 text-sm">{request.subject_name} • {request.city}</p>
          </div>
          <div class="text-right">
            <div class="font-semibold text-gray-900">{request.budget_range}</div>
            <button class="text-sm text-red-600 hover:underline mt-1">Kapat</button>
          </div>
        </div>
      {/each}
    </div>
  {/if}
</div>
