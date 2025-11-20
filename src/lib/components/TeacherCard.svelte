<script>
  export let teacher;
  
  let subjects = [];
  
  $: {
    if (Array.isArray(teacher.subjects)) {
      subjects = teacher.subjects.map(s => typeof s === 'object' ? s.name : s);
    } else if (typeof teacher.subjects === 'string') {
      subjects = teacher.subjects.split(',');
    } else {
      subjects = [];
    }
  }
</script>

<a 
  href="/profil/{teacher.id}" 
  class="block bg-white rounded-lg shadow-sm hover:shadow-md transition p-4"
>
  <div class="flex gap-4">
    <!-- Avatar -->
    <div class="flex-shrink-0">
      {#if teacher.avatar_url}
        <img 
          src={teacher.avatar_url} 
          alt={teacher.full_name}
          class="w-20 h-20 rounded-full object-cover"
        />
      {:else}
        <div class="w-20 h-20 rounded-full bg-blue-100 flex items-center justify-center text-2xl">
          {teacher.full_name.charAt(0)}
        </div>
      {/if}
    </div>
    
    <!-- Info -->
    <div class="flex-1 min-w-0">
      <div class="flex items-start justify-between">
        <div>
          <h3 class="font-semibold text-lg flex items-center gap-2">
            {teacher.full_name}
            {#if teacher.is_verified}
              <span class="text-blue-600" title="Doğrulanmış">✓</span>
            {/if}
          </h3>
          <p class="text-sm text-gray-600">{teacher.university}</p>
          <p class="text-sm text-gray-500">{teacher.department}</p>
        </div>
        <div class="text-right">
          <p class="text-lg font-bold text-blue-600">€{teacher.hourly_rate}</p>
          <p class="text-xs text-gray-500">/saat</p>
        </div>
      </div>
      
      <!-- Rating -->
      {#if teacher.review_count > 0}
        <div class="flex items-center gap-1 mt-2">
          <span class="text-yellow-500">⭐</span>
          <span class="text-sm font-semibold">{teacher.rating_avg}</span>
          <span class="text-xs text-gray-500">({teacher.review_count} yorum)</span>
        </div>
      {/if}
      
      <!-- Subjects -->
      <div class="flex flex-wrap gap-1 mt-2">
        {#each subjects.slice(0, 3) as subject}
          <span class="px-2 py-1 bg-blue-50 text-blue-700 text-xs rounded">
            {subject}
          </span>
        {/each}
        {#if subjects.length > 3}
          <span class="px-2 py-1 text-xs text-gray-500">
            +{subjects.length - 3} daha
          </span>
        {/if}
      </div>
      
      <!-- Location -->
      <p class="text-sm text-gray-500 mt-2">
        📍 {teacher.city || 'Belirtilmemiş'}
      </p>
    </div>
  </div>
</a>
