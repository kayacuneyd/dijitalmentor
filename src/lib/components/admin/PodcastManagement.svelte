<script>
  import { onMount } from 'svelte';
  import { api } from '$lib/utils/api.js';
  import { toast } from '$lib/stores/toast.js';

  let episodes = [];
  let loading = false;
  let statusFilter = 'all';

  let formOpen = false;
  let episodeForm = {
    id: null,
    topic_prompt: '',
    title: '',
    description: '',
    publish_date: new Date().toISOString().split('T')[0],
    is_published: false
  };

  let pollingInterval = null;
  let generatingEpisodeId = null;

  onMount(() => {
    loadEpisodes();
    return () => {
      if (pollingInterval) clearInterval(pollingInterval);
    };
  });

  async function loadEpisodes() {
    loading = true;
    try {
      const params = statusFilter !== 'all' ? { status: statusFilter } : {};
      const res = await api.get('/admin/podcast/list.php', params);
      episodes = res.data?.episodes || [];
    } catch (err) {
      console.error('Episodes yüklenemedi:', err);
      toast.error('Episodes yüklenemedi');
    } finally {
      loading = false;
    }
  }

  function openCreateForm() {
    episodeForm = {
      id: null,
      topic_prompt: '',
      title: '',
      description: '',
      publish_date: new Date().toISOString().split('T')[0],
      is_published: false
    };
    formOpen = true;
  }

  async function saveAndGenerate() {
    if (!episodeForm.topic_prompt.trim()) {
      toast.error('Konu başlığı gerekli');
      return;
    }

    try {
      const res = await api.post('/admin/podcast/create.php', {
        ...episodeForm,
        trigger_generation: true
      });

      if (res.success) {
        toast.success('Podcast oluşturma başlatıldı!');
        formOpen = false;

        // Start polling for status
        generatingEpisodeId = res.data.id;
        startStatusPolling(res.data.id);

        await loadEpisodes();
      } else {
        toast.error(res.error || 'Episode kaydedilemedi');
      }
    } catch (err) {
      console.error('Save error:', err);
      toast.error('İşlem başarısız');
    }
  }

  function startStatusPolling(episodeId) {
    if (pollingInterval) clearInterval(pollingInterval);

    pollingInterval = setInterval(async () => {
      try {
        const res = await api.get('/admin/podcast/status.php', { id: episodeId });
        const status = res.data?.processing_status;

        if (status === 'completed') {
          clearInterval(pollingInterval);
          generatingEpisodeId = null;
          toast.success('Podcast hazır!');
          await loadEpisodes();
        } else if (status === 'failed') {
          clearInterval(pollingInterval);
          generatingEpisodeId = null;
          toast.error('Podcast oluşturulamadı: ' + res.data.error_message);
          await loadEpisodes();
        }
      } catch (err) {
        console.error('Status polling error:', err);
      }
    }, 15000); // Poll every 15 seconds
  }

  async function deleteEpisode(id, title) {
    if (!confirm(`"${title}" adlı episode silinsin mi?`)) return;

    try {
      const res = await api.post('/admin/podcast/delete.php', { id });
      if (res.success) {
        toast.success('Episode silindi');
        await loadEpisodes();
      }
    } catch (err) {
      toast.error('Silme işlemi başarısız');
    }
  }

  function getStatusBadgeClass(status) {
    const classes = {
      pending: 'bg-gray-100 text-gray-700',
      generating: 'bg-blue-100 text-blue-700 animate-pulse',
      completed: 'bg-green-100 text-green-700',
      failed: 'bg-red-100 text-red-700'
    };
    return classes[status] || classes.pending;
  }

  function getStatusText(status) {
    const texts = {
      pending: '⏳ Bekliyor',
      generating: '⚙️ Oluşturuluyor',
      completed: '✅ Hazır',
      failed: '❌ Hata'
    };
    return texts[status] || status;
  }
</script>

<section class="space-y-4">
  <div class="flex items-center justify-between">
    <div>
      <h2 class="text-2xl font-semibold text-gray-900">Podcast Yönetimi</h2>
      <p class="text-sm text-gray-500 mt-1">Otomatik podcast oluştur ve yönet</p>
    </div>
    <div class="flex gap-3">
      <select
        bind:value={statusFilter}
        on:change={loadEpisodes}
        class="px-4 py-2 border border-gray-300 rounded-lg text-sm"
      >
        <option value="all">Tümü</option>
        <option value="pending">Bekliyor</option>
        <option value="generating">Oluşturuluyor</option>
        <option value="completed">Tamamlanan</option>
        <option value="failed">Hatalı</option>
      </select>
      <button
        on:click={openCreateForm}
        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold"
      >
        + Yeni Podcast Oluştur
      </button>
    </div>
  </div>

  {#if formOpen}
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-100 flex items-center justify-between">
          <h3 class="text-xl font-semibold">Yeni Podcast Episode</h3>
          <button on:click={() => (formOpen = false)} class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
              Konu Başlığı <span class="text-red-500">*</span>
            </label>
            <textarea
              bind:value={episodeForm.topic_prompt}
              placeholder="Örn: Almanya'da Gymnasium seçimi nasıl yapılır?"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              rows="3"
            ></textarea>
            <p class="text-xs text-gray-500 mt-1">
              Bu konuyla ilgili otomatik senaryo oluşturulacak
            </p>
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
              Başlık (opsiyonel)
            </label>
            <input
              type="text"
              bind:value={episodeForm.title}
              placeholder="Boş bırakılırsa AI oluşturur"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
              Açıklama (opsiyonel)
            </label>
            <textarea
              bind:value={episodeForm.description}
              placeholder="Boş bırakılırsa AI oluşturur"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              rows="3"
            ></textarea>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">Yayın Tarihi</label>
              <input
                type="date"
                bind:value={episodeForm.publish_date}
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div>
              <label class="flex items-center gap-2 cursor-pointer pt-8">
                <input type="checkbox" bind:checked={episodeForm.is_published} class="w-5 h-5 text-blue-600" />
                <span class="text-sm font-semibold text-gray-700">Hemen yayınla</span>
              </label>
            </div>
          </div>

          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h4 class="font-semibold text-blue-900 mb-2">🤖 Otomatik İşlemler:</h4>
            <ul class="text-sm text-blue-800 space-y-1">
              <li>✓ Claude AI ile senaryo oluşturma</li>
              <li>✓ ElevenLabs ile Türkçe seslendirme</li>
              <li>✓ Fon müziği ekleme</li>
              <li>✓ Cloudflare R2'ye yükleme</li>
              <li>✓ YouTube'a video olarak yükleme</li>
              <li>✓ Spotify RSS feed güncelleme</li>
            </ul>
            <p class="text-xs text-blue-600 mt-3">⏱️ Tahmini süre: 5-8 dakika</p>
          </div>
        </div>

        <div class="p-6 border-t border-gray-100 flex justify-end gap-3">
          <button
            on:click={() => (formOpen = false)}
            class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
          >
            İptal
          </button>
          <button
            on:click={saveAndGenerate}
            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold"
          >
            Oluştur ve Yayınla
          </button>
        </div>
      </div>
    </div>
  {/if}

  {#if loading}
    <div class="flex justify-center py-12">
      <div class="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
    </div>
  {:else}
    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Başlık</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Durum</th>
            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Süre</th>
            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Yayın</th>
            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Tarih</th>
            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Linkler</th>
            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">İşlemler</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          {#each episodes as episode}
            <tr class="hover:bg-gray-50">
              <td class="px-4 py-3">
                <div class="font-semibold text-gray-900">
                  {episode.title || episode.topic_prompt}
                </div>
                {#if episode.description}
                  <div class="text-xs text-gray-500 mt-1 line-clamp-1">{episode.description}</div>
                {/if}
              </td>
              <td class="px-4 py-3">
                <span class={`px-3 py-1 rounded-full text-xs font-semibold ${getStatusBadgeClass(episode.processing_status)}`}>
                  {getStatusText(episode.processing_status)}
                </span>
              </td>
              <td class="px-4 py-3 text-center text-sm text-gray-700">
                {episode.duration_formatted || '-'}
              </td>
              <td class="px-4 py-3 text-center">
                <span class={`px-2 py-1 rounded text-xs ${episode.is_published ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'}`}>
                  {episode.is_published ? 'Yayında' : 'Taslak'}
                </span>
              </td>
              <td class="px-4 py-3 text-center text-sm text-gray-600">
                {new Date(episode.publish_date || episode.created_at).toLocaleDateString('tr-TR')}
              </td>
              <td class="px-4 py-3 text-center">
                <div class="flex gap-2 justify-center">
                  {#if episode.audio_url}
                    <a href={episode.audio_url} target="_blank" class="text-blue-600 hover:text-blue-800" title="MP3">
                      🎵
                    </a>
                  {/if}
                  {#if episode.youtube_video_id}
                    <a
                      href={`https://www.youtube.com/watch?v=${episode.youtube_video_id}`}
                      target="_blank"
                      class="text-red-600 hover:text-red-800"
                      title="YouTube"
                    >
                      📺
                    </a>
                  {/if}
                  {#if episode.processing_status === 'completed'}
                    <a
                      href={`https://dijitalmentor.de/podcast/${episode.slug}`}
                      target="_blank"
                      class="text-green-600 hover:text-green-800"
                      title="Website"
                    >
                      🌐
                    </a>
                  {/if}
                </div>
              </td>
              <td class="px-4 py-3 text-center">
                <button
                  on:click={() => deleteEpisode(episode.id, episode.title || episode.topic_prompt)}
                  class="px-3 py-1 text-xs rounded bg-red-100 text-red-700 hover:bg-red-200"
                >
                  Sil
                </button>
              </td>
            </tr>
          {:else}
            <tr>
              <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                Henüz podcast oluşturulmamış. Yukarıdaki butona tıklayarak başlayın!
              </td>
            </tr>
          {/each}
        </tbody>
      </table>
    </div>
  {/if}

  {#if generatingEpisodeId}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 flex items-center gap-3">
      <div class="w-5 h-5 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
      <div>
        <p class="font-semibold text-blue-900">Podcast oluşturuluyor...</p>
        <p class="text-sm text-blue-700">İşlem tamamlandığında otomatik güncellenecek</p>
      </div>
    </div>
  {/if}
</section>
