<script>
  export let episode;
</script>

<a
  href={`/podcast/${episode.slug}`}
  class="group block bg-white rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden"
>
  <!-- Cover Image -->
  <div class="relative aspect-square overflow-hidden">
    {#if episode.cover_image_url}
      <img
        src={episode.cover_image_url}
        alt={episode.title}
        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
      />
    {:else}
      <div class="w-full h-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center">
        <span class="text-white text-6xl">🎙️</span>
      </div>
    {/if}

    <!-- Play Icon Overlay -->
    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-40 transition-all duration-300 flex items-center justify-center">
      <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-300">
        <svg class="w-16 h-16 text-white" viewBox="0 0 24 24" fill="currentColor">
          <path d="M8 5v14l11-7z"/>
        </svg>
      </div>
    </div>

    <!-- Duration Badge -->
    {#if episode.duration_formatted}
      <div class="absolute bottom-2 right-2 bg-black bg-opacity-75 text-white text-xs px-2 py-1 rounded">
        {episode.duration_formatted}
      </div>
    {/if}
  </div>

  <!-- Episode Info -->
  <div class="p-5">
    <h3 class="text-lg font-semibold text-gray-900 mb-2 line-clamp-2 group-hover:text-blue-600 transition-colors">
      {episode.title}
    </h3>

    {#if episode.description}
      <p class="text-sm text-gray-600 line-clamp-2 mb-3">
        {episode.description}
      </p>
    {/if}

    <div class="flex items-center justify-between text-xs text-gray-500">
      <span class="flex items-center gap-1">
        📅 {new Date(episode.publish_date || episode.created_at).toLocaleDateString('tr-TR', { year: 'numeric', month: 'short', day: 'numeric' })}
      </span>
    </div>
  </div>
</a>
