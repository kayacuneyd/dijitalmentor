<script>
  import { onMount } from 'svelte';
  import { api } from '$lib/utils/api.js';
  import PodcastEpisodeCard from '$lib/components/PodcastEpisodeCard.svelte';

  let episodes = [];
  let loading = true;
  let error = null;
  let pagination = { page: 1, total: 0, total_pages: 1 };

  onMount(() => {
    loadEpisodes();
  });

  async function loadEpisodes(page = 1) {
    loading = true;
    error = null;

    try {
      const res = await api.get('/podcast/list.php', { page, limit: 12 });

      if (res.success) {
        episodes = res.data.episodes || [];
        pagination = res.data.pagination || pagination;
      } else {
        error = res.error || 'Episodes yüklenemedi';
      }
    } catch (err) {
      console.error('Load episodes error:', err);
      error = 'Bağlantı hatası';
    } finally {
      loading = false;
    }
  }

  function changePage(newPage) {
    if (newPage >= 1 && newPage <= pagination.total_pages) {
      pagination.page = newPage;
      loadEpisodes(newPage);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  }
</script>

<svelte:head>
  <title>Podcast - Dijital Mentor</title>
  <meta name="description" content="Almanya'daki Türk aileleri için eğitim, teknoloji ve dijital dünyada çocuklar konulu podcast serimiz." />
</svelte:head>

<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50">
  <!-- Hero Section -->
  <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white py-16">
    <div class="container mx-auto px-4">
      <div class="max-w-4xl mx-auto text-center">
        <h1 class="text-4xl md:text-5xl font-bold mb-4">
          🎙️ Dijital Mentor Podcast
        </h1>
        <p class="text-lg md:text-xl text-blue-100 mb-6">
          Almanya'daki Türk aileleri için eğitim, teknoloji ve dijital dünyada çocuklar
        </p>
        <div class="flex flex-wrap justify-center gap-4">
          <a
            href="https://open.spotify.com/show/YOUR_SHOW_ID"
            target="_blank"
            class="px-6 py-3 bg-green-500 hover:bg-green-600 rounded-full font-semibold flex items-center gap-2 transition"
          >
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.521 17.34c-.24.359-.66.48-1.021.24-2.82-1.74-6.36-2.101-10.561-1.141-.418.122-.779-.179-.899-.539-.12-.421.18-.78.54-.9 4.56-1.021 8.52-.6 11.64 1.32.42.18.479.659.301 1.02zm1.44-3.3c-.301.42-.841.6-1.262.3-3.239-1.98-8.159-2.58-11.939-1.38-.479.12-1.02-.12-1.14-.6-.12-.48.12-1.021.6-1.141C9.6 9.9 15 10.561 18.72 12.84c.361.181.54.78.241 1.2zm.12-3.36C15.24 8.4 8.82 8.16 5.16 9.301c-.6.179-1.2-.181-1.38-.721-.18-.601.18-1.2.72-1.381 4.26-1.26 11.28-1.02 15.721 1.621.539.3.719 1.02.419 1.56-.299.421-1.02.599-1.559.3z"/>
            </svg>
            Spotify'da Dinle
          </a>
          <a
            href="https://www.youtube.com/@dijitalmentoryoutube"
            target="_blank"
            class="px-6 py-3 bg-red-500 hover:bg-red-600 rounded-full font-semibold flex items-center gap-2 transition"
          >
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
              <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
            </svg>
            YouTube'da İzle
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Episodes Grid -->
  <div class="container mx-auto px-4 py-12">
    {#if loading}
      <div class="flex justify-center py-20">
        <div class="w-12 h-12 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
      </div>
    {:else if error}
      <div class="max-w-md mx-auto text-center py-20">
        <div class="text-red-600 text-5xl mb-4">⚠️</div>
        <h2 class="text-2xl font-semibold text-gray-900 mb-2">Bir Hata Oluştu</h2>
        <p class="text-gray-600 mb-6">{error}</p>
        <button
          on:click={() => loadEpisodes()}
          class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
        >
          Tekrar Dene
        </button>
      </div>
    {:else if episodes.length === 0}
      <div class="max-w-md mx-auto text-center py-20">
        <div class="text-gray-400 text-6xl mb-4">🎙️</div>
        <h2 class="text-2xl font-semibold text-gray-900 mb-2">Henüz Podcast Yok</h2>
        <p class="text-gray-600">Yakında ilk bölümlerimiz yayında olacak!</p>
      </div>
    {:else}
      <div class="max-w-6xl mx-auto">
        <h2 class="text-2xl font-semibold text-gray-900 mb-8">
          Tüm Bölümler ({pagination.total})
        </h2>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
          {#each episodes as episode}
            <PodcastEpisodeCard {episode} />
          {/each}
        </div>

        <!-- Pagination -->
        {#if pagination.total_pages > 1}
          <div class="flex justify-center items-center gap-2 mt-12">
            <button
              on:click={() => changePage(pagination.page - 1)}
              disabled={pagination.page === 1}
              class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              ← Önceki
            </button>

            <div class="flex gap-2">
              {#each Array(pagination.total_pages) as _, i}
                {@const pageNum = i + 1}
                {#if pageNum === 1 || pageNum === pagination.total_pages || (pageNum >= pagination.page - 1 && pageNum <= pagination.page + 1)}
                  <button
                    on:click={() => changePage(pageNum)}
                    class={`px-4 py-2 rounded-lg ${pageNum === pagination.page ? 'bg-blue-600 text-white' : 'border border-gray-300 hover:bg-gray-50'}`}
                  >
                    {pageNum}
                  </button>
                {:else if pageNum === pagination.page - 2 || pageNum === pagination.page + 2}
                  <span class="px-2 py-2">...</span>
                {/if}
              {/each}
            </div>

            <button
              on:click={() => changePage(pagination.page + 1)}
              disabled={pagination.page === pagination.total_pages}
              class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Sonraki →
            </button>
          </div>
        {/if}
      </div>
    {/if}
  </div>

  <!-- RSS Feed Link -->
  <div class="bg-gray-100 py-8">
    <div class="container mx-auto px-4 text-center">
      <p class="text-gray-700 mb-4">RSS Feed ile takip edin:</p>
      <code class="bg-white px-4 py-2 rounded border border-gray-300 text-sm">
        https://dijitalmentor.de/podcast/feed.xml
      </code>
    </div>
  </div>
</div>
