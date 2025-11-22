<script>
  import { createEventDispatcher } from 'svelte';
  import { get } from 'svelte/store';
  import { authStore } from '$lib/stores/auth.js';
  import { api } from '$lib/utils/api.js';
  import { toast } from '$lib/stores/toast.js';

  export let currentCvUrl = '';

  const dispatch = createEventDispatcher();
  let uploading = false;
  let showPremiumModal = false;

  $: user = $authStore.user;
  $: premiumActive = isPremiumActive();

  function isPremiumActive() {
    if (!user?.is_premium) return false;
    if (!user.premium_expires_at) return true;
    return new Date(user.premium_expires_at) > new Date();
  }

  async function handleFile(event) {
    const file = event.target.files?.[0];
    if (!file) return;

    if (!premiumActive) return;

    if (file.type !== 'application/pdf') {
      toast.error('Sadece PDF dosyaları kabul edilir');
      event.target.value = '';
      return;
    }

    if (file.size > 5 * 1024 * 1024) {
      toast.error('Dosya çok büyük. Maksimum 5MB olmalı');
      event.target.value = '';
      return;
    }

    const formData = new FormData();
    formData.append('cv', file);

    uploading = true;
    try {
      const res = await api.postForm('/upload/cv.php', formData);
      currentCvUrl = res.data?.cv_url;

      const state = get(authStore);
      if (state?.token && state?.user) {
        authStore.login({ ...state.user, cv_url: currentCvUrl }, state.token);
      }

      toast.success(res.message || 'CV yüklendi');
      dispatch('uploaded', res.data);
    } catch (err) {
      console.error(err);
      toast.error(err.message || 'CV yüklenemedi');
    } finally {
      uploading = false;
      event.target.value = '';
    }
  }
</script>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
  <div class="flex items-start justify-between">
    <div>
      <h3 class="text-lg font-semibold text-gray-900">CV Yükle (Premium)</h3>
      <p class="text-sm text-gray-500">PDF formatında, maksimum 5MB. Premium üyelik gerektirir.</p>
    </div>
    {#if !isPremiumActive()}
      <span class="px-3 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded-full">Premium</span>
    {:else}
      <span class="px-3 py-1 bg-green-50 text-green-700 text-xs font-semibold rounded-full">Aktif</span>
    {/if}
  </div>

  <div class="flex items-center gap-3">
    {#if premiumActive}
      <label class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition disabled:opacity-50">
        <span>📄</span>
        <span>{uploading ? 'Yükleniyor...' : 'CV Seç'}</span>
        <input type="file" accept="application/pdf" class="hidden" on:change={handleFile} disabled={uploading} />
      </label>
    {:else}
      <button
        type="button"
        class="inline-flex items-center gap-2 px-4 py-2 bg-purple-100 text-purple-700 rounded-lg hover:bg-purple-200 transition"
        on:click={() => (showPremiumModal = true)}
      >
        <span>🔒</span>
        <span>Premium gerekli</span>
      </button>
    {/if}

    {#if currentCvUrl}
      <a href={currentCvUrl} target="_blank" rel="noreferrer" class="text-sm font-semibold text-blue-700 underline">
        Mevcut CV'yi gör
      </a>
    {/if}
  </div>

  {#if !isPremiumActive()}
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-sm text-yellow-800">
      Premium üyeliğiniz yok veya süresi dolmuş. CV yüklemek için premium gerekli.
    </div>
  {/if}
</div>

{#if showPremiumModal}
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 backdrop-blur-sm">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl">
      <div class="text-center mb-6">
        <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl">
          ⭐
        </div>
        <h3 class="text-xl font-bold text-gray-900">Premium Üyelik Gerekli</h3>
        <p class="text-gray-600 mt-4 leading-relaxed">
          CV yükleme özelliği sadece premium öğretmenlere açıktır. 10€/yıl karşılığında
          <span class="font-semibold text-gray-900"> Amazon hediye kartı</span> ile aktivasyon yapabilirsiniz.
        </p>

        <div class="bg-gray-50 p-4 rounded-xl mt-6 border border-gray-100">
          <p class="text-sm text-gray-500 mb-1">E-posta Adresi</p>
          <p class="text-lg font-mono font-bold text-blue-600 select-all">hediye@dijitalmentor.de</p>
        </div>
      </div>

      <button
        class="w-full bg-gray-900 text-white font-bold py-3 rounded-xl hover:bg-gray-800 transition"
        on:click={() => (showPremiumModal = false)}
      >
        Tamam
      </button>
    </div>
  </div>
{/if}
