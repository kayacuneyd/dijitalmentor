<script>
  import { onMount, onDestroy } from 'svelte';

  export let audioUrl;
  export let title = 'Podcast';

  let audio;
  let playing = false;
  let currentTime = 0;
  let duration = 0;
  let volume = 1;
  let playbackRate = 1;
  let loading = false;

  onMount(() => {
    audio = new Audio(audioUrl);

    audio.addEventListener('loadedmetadata', () => {
      duration = audio.duration;
    });

    audio.addEventListener('timeupdate', () => {
      currentTime = audio.currentTime;
    });

    audio.addEventListener('ended', () => {
      playing = false;
      currentTime = 0;
    });

    audio.addEventListener('play', () => {
      playing = true;
    });

    audio.addEventListener('pause', () => {
      playing = false;
    });

    audio.addEventListener('waiting', () => {
      loading = true;
    });

    audio.addEventListener('canplay', () => {
      loading = false;
    });
  });

  onDestroy(() => {
    if (audio) {
      audio.pause();
      audio.src = '';
    }
  });

  function togglePlay() {
    if (playing) {
      audio.pause();
    } else {
      audio.play();
    }
  }

  function seek(e) {
    const rect = e.currentTarget.getBoundingClientRect();
    const pos = (e.clientX - rect.left) / rect.width;
    audio.currentTime = pos * duration;
  }

  function changeVolume(e) {
    volume = parseFloat(e.target.value);
    audio.volume = volume;
  }

  function changePlaybackRate(rate) {
    playbackRate = rate;
    audio.playbackRate = rate;
  }

  function skip(seconds) {
    audio.currentTime = Math.max(0, Math.min(duration, audio.currentTime + seconds));
  }

  function formatTime(seconds) {
    if (isNaN(seconds)) return '0:00';
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, '0')}`;
  }

  function downloadAudio() {
    const a = document.createElement('a');
    a.href = audioUrl;
    a.download = `${title}.mp3`;
    a.click();
  }
</script>

<div class="space-y-4">
  <!-- Title -->
  <div class="flex items-center justify-between">
    <h3 class="font-semibold text-gray-900">🎧 {title}</h3>
    <button
      on:click={downloadAudio}
      class="text-sm text-gray-600 hover:text-gray-900 flex items-center gap-1"
      title="İndir"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
      </svg>
      İndir
    </button>
  </div>

  <!-- Progress Bar -->
  <div class="space-y-2">
    <button
      on:click={seek}
      class="w-full h-2 bg-gray-200 rounded-full cursor-pointer overflow-hidden relative"
    >
      <div
        class="absolute inset-y-0 left-0 bg-blue-600 rounded-full transition-all"
        style="width: {duration ? (currentTime / duration) * 100 : 0}%"
      ></div>
    </button>

    <div class="flex justify-between text-xs text-gray-500">
      <span>{formatTime(currentTime)}</span>
      <span>{formatTime(duration)}</span>
    </div>
  </div>

  <!-- Controls -->
  <div class="flex items-center justify-center gap-4">
    <!-- Skip Backward 15s -->
    <button
      on:click={() => skip(-15)}
      class="p-2 text-gray-600 hover:text-gray-900 transition"
      title="15 saniye geri"
    >
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.333 4zM4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z"/>
      </svg>
    </button>

    <!-- Play/Pause -->
    <button
      on:click={togglePlay}
      class="p-4 bg-blue-600 text-white rounded-full hover:bg-blue-700 transition shadow-lg"
      disabled={loading}
    >
      {#if loading}
        <svg class="w-8 h-8 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
      {:else if playing}
        <!-- Pause Icon -->
        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
          <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/>
        </svg>
      {:else}
        <!-- Play Icon -->
        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
          <path d="M8 5v14l11-7z"/>
        </svg>
      {/if}
    </button>

    <!-- Skip Forward 15s -->
    <button
      on:click={() => skip(15)}
      class="p-2 text-gray-600 hover:text-gray-900 transition"
      title="15 saniye ileri"
    >
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.933 12.8a1 1 0 000-1.6L6.6 7.2A1 1 0 005 8v8a1 1 0 001.6.8l5.333-4zM19.933 12.8a1 1 0 000-1.6l-5.333-4A1 1 0 0013 8v8a1 1 0 001.6.8l5.333-4z"/>
      </svg>
    </button>
  </div>

  <!-- Additional Controls -->
  <div class="flex items-center justify-between">
    <!-- Playback Speed -->
    <div class="flex items-center gap-2">
      <span class="text-xs text-gray-600">Hız:</span>
      {#each [0.75, 1, 1.25, 1.5, 2] as rate}
        <button
          on:click={() => changePlaybackRate(rate)}
          class={`px-2 py-1 text-xs rounded ${playbackRate === rate ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'}`}
        >
          {rate}x
        </button>
      {/each}
    </div>

    <!-- Volume -->
    <div class="flex items-center gap-2">
      <svg class="w-4 h-4 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217zM14.657 2.929a1 1 0 011.414 0A9.972 9.972 0 0119 10a9.972 9.972 0 01-2.929 7.071 1 1 0 01-1.414-1.414A7.971 7.971 0 0017 10c0-2.21-.894-4.208-2.343-5.657a1 1 0 010-1.414zm-2.829 2.828a1 1 0 011.415 0A5.983 5.983 0 0115 10a5.984 5.984 0 01-1.757 4.243 1 1 0 01-1.415-1.415A3.984 3.984 0 0013 10a3.983 3.983 0 00-1.172-2.828 1 1 0 010-1.415z" clip-rule="evenodd"/>
      </svg>
      <input
        type="range"
        min="0"
        max="1"
        step="0.1"
        value={volume}
        on:input={changeVolume}
        class="w-20 h-1 bg-gray-200 rounded-lg appearance-none cursor-pointer"
      />
    </div>
  </div>
</div>

<style>
  /* Custom range slider styling */
  input[type='range']::-webkit-slider-thumb {
    appearance: none;
    width: 12px;
    height: 12px;
    background: #2563eb;
    border-radius: 50%;
    cursor: pointer;
  }

  input[type='range']::-moz-range-thumb {
    width: 12px;
    height: 12px;
    background: #2563eb;
    border-radius: 50%;
    cursor: pointer;
    border: none;
  }
</style>
