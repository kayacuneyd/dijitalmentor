<script>
  import { createEventDispatcher } from 'svelte';
  import { api } from '$lib/utils/api.js';
  import { toast } from '$lib/stores/toast.js';

  export let conversationId = null;
  export let teacherId = null;
  export let parentId = null;
  export let subjects = [];

  const dispatch = createEventDispatcher();

  let submitting = false;
  let form = {
    subject_id: subjects?.[0]?.id || '',
    lesson_date: '',
    lesson_time: '',
    location: 'online',
    address_detail: '',
    agreed_price: 20,
    agreed_duration: 1.0
  };

  $: if (subjects.length && !form.subject_id) {
    form.subject_id = subjects[0].id;
  }

  $: requiresAddress = form.location === 'in_person' || form.location === 'address';

  async function handleSubmit() {
    if (submitting) return;

    if (!form.subject_id || !form.lesson_date || !form.agreed_price || !form.agreed_duration) {
      toast.error('Lütfen tüm zorunlu alanları doldurun');
      return;
    }

    if (requiresAddress && !form.address_detail.trim()) {
      toast.error('Fiziksel dersler için adres girin');
      return;
    }

    submitting = true;
    try {
      const payload = {
        conversation_id: conversationId,
        teacher_id: teacherId,
        parent_id: parentId,
        subject_id: Number(form.subject_id),
        lesson_date: form.lesson_date,
        lesson_time: form.lesson_time || null,
        location: form.location,
        address_detail: requiresAddress ? form.address_detail.trim() : null,
        agreed_price: Number(form.agreed_price),
        agreed_duration: Number(form.agreed_duration),
        status: 'pending'
      };

      const res = await api.post('/lessons/create_agreement.php', payload);
      toast.success('Anlaşma başarıyla oluşturuldu');
      dispatch('success', res.data);
      
      // Reset form
      form = {
        subject_id: subjects?.[0]?.id || '',
        lesson_date: '',
        lesson_time: '',
        location: 'online',
        address_detail: '',
        agreed_price: 20,
        agreed_duration: 1.0
      };
    } catch (err) {
      console.error(err);
      toast.error(err.message || 'Anlaşma oluşturulamadı');
    } finally {
      submitting = false;
    }
  }
</script>

<div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm space-y-4">
  <div class="flex items-center justify-between">
    <div>
      <h3 class="text-base font-semibold text-gray-900">Ders Anlaşması Oluştur</h3>
      <p class="text-sm text-gray-500">Ders detaylarını belirleyin (yer, saat, fiyat)</p>
    </div>
  </div>

  <form on:submit|preventDefault={handleSubmit} class="space-y-4">
    <div class="grid md:grid-cols-2 gap-3">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1" for="lesson-subject">Ders *</label>
        <select
          id="lesson-subject"
          class="w-full border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-sm"
          bind:value={form.subject_id}
          required
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
        <label class="block text-sm font-medium text-gray-700 mb-1" for="lesson-date">Ders Tarihi *</label>
        <input
          id="lesson-date"
          type="date"
          class="w-full border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-sm"
          bind:value={form.lesson_date}
          required
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1" for="lesson-time">Ders Saati</label>
        <input
          id="lesson-time"
          type="time"
          class="w-full border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-sm"
          bind:value={form.lesson_time}
        />
      </div>

      <div>
        <p class="block text-sm font-medium text-gray-700 mb-1">Lokasyon *</p>
        <div class="grid grid-cols-3 gap-2">
          <button
            type="button"
            class={`border rounded-lg px-3 py-2 text-sm transition ${
              form.location === 'online' 
                ? 'border-blue-500 text-blue-700 bg-blue-50' 
                : 'border-gray-200 text-gray-700 hover:bg-gray-50'
            }`}
            on:click={() => {
              form.location = 'online';
              form.address_detail = '';
            }}
          >
            Online
          </button>
          <button
            type="button"
            class={`border rounded-lg px-3 py-2 text-sm transition ${
              form.location === 'in_person' 
                ? 'border-blue-500 text-blue-700 bg-blue-50' 
                : 'border-gray-200 text-gray-700 hover:bg-gray-50'
            }`}
            on:click={() => form.location = 'in_person'}
          >
            Yüz Yüze
          </button>
          <button
            type="button"
            class={`border rounded-lg px-3 py-2 text-sm transition ${
              form.location === 'address' 
                ? 'border-blue-500 text-blue-700 bg-blue-50' 
                : 'border-gray-200 text-gray-700 hover:bg-gray-50'
            }`}
            on:click={() => form.location = 'address'}
          >
            Adres
          </button>
        </div>
      </div>

      {#if requiresAddress}
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1" for="lesson-address">Adres *</label>
          <input
            id="lesson-address"
            type="text"
            class="w-full border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-sm"
            placeholder="Ders yapılacak adres"
            bind:value={form.address_detail}
            required={requiresAddress}
          />
        </div>
      {/if}

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1" for="lesson-price">Saatlik Ücret (€) *</label>
        <input
          id="lesson-price"
          type="number"
          min="0"
          step="0.5"
          class="w-full border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-sm"
          bind:value={form.agreed_price}
          required
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1" for="lesson-duration">Süre (Saat) *</label>
        <input
          id="lesson-duration"
          type="number"
          min="0.5"
          step="0.5"
          class="w-full border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-sm"
          bind:value={form.agreed_duration}
          required
        />
      </div>
    </div>

    <div class="flex justify-end gap-2 pt-2">
      <button
        type="submit"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 transition disabled:opacity-50"
        disabled={submitting || subjects.length === 0}
      >
        {submitting ? 'Oluşturuluyor...' : 'Anlaşma Oluştur'}
      </button>
    </div>
  </form>
</div>

