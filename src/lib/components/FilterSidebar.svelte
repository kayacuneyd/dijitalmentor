<script>
  import { createEventDispatcher } from 'svelte';
  
  export let subjects = [];
  export let filters = {};
  
  const dispatch = createEventDispatcher();
  
  let localFilters = { ...filters };
  
  const cities = [
    'Berlin', 'Hamburg', 'München', 'Köln', 'Frankfurt',
    'Stuttgart', 'Düsseldorf', 'Dortmund', 'Essen', 'Leipzig'
  ];
  
  function applyFilters() {
    dispatch('filter', localFilters);
  }
  
  function resetFilters() {
    localFilters = { city: '', subject: '', max_rate: null };
    dispatch('filter', localFilters);
  }
</script>

<div class="bg-white rounded-lg shadow-sm p-6 sticky top-20">
  <h2 class="text-xl font-bold mb-4">Filtrele</h2>
  
  <!-- Şehir -->
  <div class="mb-4">
    <label class="block text-sm font-semibold mb-2">Şehir</label>
    <select 
      bind:value={localFilters.city}
      on:change={applyFilters}
      class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
    >
      <option value="">Tümü</option>
      {#each cities as city}
        <option value={city}>{city}</option>
      {/each}
    </select>
  </div>
  
  <!-- Ders -->
  <div class="mb-4">
    <label class="block text-sm font-semibold mb-2">Ders</label>
    <select 
      bind:value={localFilters.subject}
      on:change={applyFilters}
      class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
    >
      <option value="">Tümü</option>
      {#each subjects as subject}
        <option value={subject.slug}>
          {subject.icon} {subject.name}
        </option>
      {/each}
    </select>
  </div>
  
  <!-- Maksimum Ücret -->
  <div class="mb-4">
    <label class="block text-sm font-semibold mb-2">
      Maksimum Ücret: €{localFilters.max_rate || '∞'}
    </label>
    <input 
      type="range" 
      min="10" 
      max="50" 
      step="5"
      bind:value={localFilters.max_rate}
      on:change={applyFilters}
      class="w-full"
    />
    <div class="flex justify-between text-xs text-gray-500 mt-1">
      <span>€10</span>
      <span>€50</span>
    </div>
  </div>
  
  <!-- Reset -->
  <button 
    on:click={resetFilters}
    class="w-full text-sm text-gray-600 hover:text-gray-800"
  >
    Filtreleri Temizle
  </button>
</div>
