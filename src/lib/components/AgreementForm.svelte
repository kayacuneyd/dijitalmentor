<script>
  import { createEventDispatcher } from 'svelte';
  import { api } from '$lib/utils/api.js';
  import { toast } from '$lib/stores/toast.js';

  export let conversationId;
  export let recipientId; // opsiyonel, backend konuşmadan bulacak
  export let subjects = [];

  const dispatch = createEventDispatcher();

  let submitting = false;
  let form = {
    subject_id: subjects?.[0]?.id || '',
    lesson_location: 'online',
    lesson_address: '',
    hourly_rate: 25,
    hours_per_week: 1,
    start_date: '',
    notes: ''
  };

  $: if (subjects.length && !form.subject_id) {
    form.subject_id = subjects[0].id;
  }

  $: isOnline = form.lesson_location === 'online';

  async function handleSubmit() {
    if (submitting) return;

    if (!subjects.length) {
      toast.error('Ders listesi yüklenemedi');
      return;
    }

    if (!form.subject_id || !form.hourly_rate) {
      toast.error('Ders ve ücret alanları zorunludur');
      return;
    }

    if (!isOnline && !form.lesson_address.trim()) {
      toast.error('Fiziksel dersler için adres girin');
      return;
    }

    submitting = true;
    try {
      const payload = {
        conversation_id: conversationId,
        recipient_id: recipientId,
        subject_id: Number(form.subject_id),
        lesson_location: form.lesson_location,
        hourly_rate: Number(form.hourly_rate),
        hours_per_week: Number(form.hours_per_week) || 1,
        start_date: form.start_date || null,
        notes: form.notes?.trim() || null,
        lesson_address: form.lesson_address?.trim() || null,
        meeting_platform: 'jitsi'
      };

      const res = await api.post('/agreements/create.php', payload);
      toast.success(res.message || 'Onay formu gönderildi');
      dispatch('success', res.data);
    } catch (err) {
      console.error(err);
      toast.error(err.message || 'Onay formu oluşturulamadı');
    } finally {
      submitting = false;
    }
  }
</script>

<div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm space-y-4">
  <div class="flex items-center justify-between">
    <div>
      <h3 class="text-base font-semibold text-gray-900">Onay Formu Oluştur</h3>
      <p class="text-sm text-gray-500">Ders detaylarını belirleyip Jitsi linki oluşturun.</p>
    </div>
    <span class="px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-semibold">Jitsi</span>
  </div>

  <div class="grid md:grid-cols-2 gap-3">
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Ders</label>
      <select
        class="w-full border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-sm"
        bind:value={form.subject_id}
      >
        {#if subjects.length === 0}
          <option value="">Dersler yükleniyor...</option>
        {:else}
          {#each subjects as subject}
            <option value={subject.id}>{subject.icon} {subject.name}</option>
          {/each}
        {/if}
      </select>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Lokasyon</label>
      <div class="grid grid-cols-3 gap-2">
        <button
          type="button"
          class={`border rounded-lg px-3 py-2 text-sm ${form.lesson_location === 'student_home' ? 'border-blue-500 text-blue-700 bg-blue-50' : 'border-gray-200 text-gray-700'}`}
          on:click={() => form.lesson_location = 'student_home'}
        >Öğrenci evi</button>
        <button
          type="button"
          class={`border rounded-lg px-3 py-2 text-sm ${form.lesson_location === 'turkish_center' ? 'border-blue-500 text-blue-700 bg-blue-50' : 'border-gray-200 text-gray-700'}`}
          on:click={() => form.lesson_location = 'turkish_center'}
        >Dernek</button>
        <button
          type="button"
          class={`border rounded-lg px-3 py-2 text-sm ${isOnline ? 'border-blue-500 text-blue-700 bg-blue-50' : 'border-gray-200 text-gray-700'}`}
          on:click={() => form.lesson_location = 'online'}
        >Online</button>
      </div>
    </div>

    {#if !isOnline}
      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Adres</label>
        <input
          type="text"
          class="w-full border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-sm"
          placeholder="Adres veya dernek bilgisi"
          bind:value={form.lesson_address}
        />
      </div>
    {/if}

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Saatlik Ücret (€)</label>
      <input
        type="number"
        min="0"
        step="0.5"
        class="w-full border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-sm"
        bind:value={form.hourly_rate}
      />
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Haftalık Saat</label>
      <input
        type="number"
        min="1"
        class="w-full border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-sm"
        bind:value={form.hours_per_week}
      />
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Başlangıç Tarihi</label>
      <input
        type="date"
        class="w-full border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-sm"
        bind:value={form.start_date}
      />
    </div>

    <div class="md:col-span-2">
      <label class="block text-sm font-medium text-gray-700 mb-1">Notlar</label>
      <textarea
        rows="3"
        class="w-full border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-sm"
        placeholder="Örn: Haftada 2 saat online matematik..."
        bind:value={form.notes}
      ></textarea>
    </div>
  </div>

  <div class="flex justify-end gap-2">
    <button
      type="button"
      class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 transition disabled:opacity-50"
      on:click={handleSubmit}
      disabled={submitting || subjects.length === 0}
    >
      {submitting ? 'Gönderiliyor...' : 'Onay Formu Gönder'}
    </button>
  </div>
</div>
