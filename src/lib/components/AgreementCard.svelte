<script>
  import { createEventDispatcher } from 'svelte';
  import { authStore } from '$lib/stores/auth.js';
  import { api } from '$lib/utils/api.js';
  import { toast } from '$lib/stores/toast.js';

  export let agreement;

  const dispatch = createEventDispatcher();
  let responding = false;
  let loggingHours = false;
  let hoursInput = '';
  let hoursNote = '';

  $: currentUser = $authStore.user;
  $: isMine = currentUser?.id === agreement?.sender_id;
  $: isRecipient = currentUser?.id === agreement?.recipient_id;
  $: canRespond = agreement?.status === 'pending' && isRecipient;
  $: canCancel = agreement?.status === 'pending' && (isRecipient || isMine);
  $: subjectIcon = agreement?.subject_icon || '📘';
  $: subjectName = agreement?.subject_name || 'Ders';

  const locationLabels = {
    student_home: 'Öğrenci evi',
    turkish_center: 'Dernek',
    online: 'Online'
  };

  async function respond(status) {
    if (responding) return;
    responding = true;
    try {
      await api.post('/agreements/respond.php', {
        agreement_id: agreement.id,
        status
      });
      toast.success(status === 'accepted' ? 'Onaylandı' : status === 'rejected' ? 'Reddedildi' : 'İptal edildi');
      dispatch('responded', { id: agreement.id, status });
    } catch (err) {
      console.error(err);
      toast.error(err.message || 'İşlem başarısız');
    } finally {
      responding = false;
    }
  }

  async function logHours() {
    if (loggingHours) return;
    const hours = parseFloat(hoursInput);
    if (!hours || hours <= 0) {
      toast.error('Geçerli bir saat değeri girin');
      return;
    }
    loggingHours = true;
    try {
      const res = await api.post('/rewards/track_hours.php', {
        agreement_id: agreement.id,
        hours_completed: hours,
        notes: hoursNote?.trim() || null
      });
      hoursInput = '';
      hoursNote = '';
      const rewardNote = res.data?.new_rewards?.length
        ? ` 🎉 ${res.data.new_rewards.length} ödül kazandınız`
        : '';
      toast.success(`Saat kaydedildi. Toplam: ${res.data?.total_hours || hours} saat.${rewardNote}`);
      dispatch('hoursLogged', res.data);
    } catch (err) {
      console.error(err);
      toast.error(err.message || 'Saat kaydedilemedi');
    } finally {
      loggingHours = false;
    }
  }
</script>

<div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm space-y-3">
  <div class="flex items-start justify-between gap-3">
    <div>
      <div class="text-sm text-gray-500 flex items-center gap-2">
        <span class="text-lg">{subjectIcon}</span>
        <span class="font-semibold text-gray-900">{subjectName}</span>
        <span class="text-gray-400">•</span>
        <span>{locationLabels[agreement.lesson_location] || agreement.lesson_location}</span>
      </div>
      <div class="text-xs text-gray-500 mt-1">
        Gönderen: {agreement.sender_name} · Alıcı: {agreement.recipient_name}
      </div>
    </div>
    <span class={`px-3 py-1 rounded-full text-xs font-semibold ${
      agreement.status === 'accepted'
        ? 'bg-green-50 text-green-700 border border-green-100'
        : agreement.status === 'pending'
        ? 'bg-yellow-50 text-yellow-700 border border-yellow-100'
        : 'bg-gray-100 text-gray-600 border border-gray-200'
    }`}>
      {agreement.status}
    </span>
  </div>

  <div class="grid md:grid-cols-3 gap-3 text-sm text-gray-700">
    <div class="p-3 bg-gray-50 rounded-lg border border-gray-100">
      <div class="text-xs text-gray-500">Ücret</div>
      <div class="font-semibold">€{agreement.hourly_rate} / saat</div>
    </div>
    <div class="p-3 bg-gray-50 rounded-lg border border-gray-100">
      <div class="text-xs text-gray-500">Haftalık</div>
      <div class="font-semibold">{agreement.hours_per_week} saat</div>
    </div>
    <div class="p-3 bg-gray-50 rounded-lg border border-gray-100">
      <div class="text-xs text-gray-500">Başlangıç</div>
      <div class="font-semibold">{agreement.start_date || 'Belirtilmedi'}</div>
    </div>
  </div>

  {#if agreement.lesson_address && agreement.lesson_location !== 'online'}
    <div class="text-sm text-gray-700">
      <span class="font-semibold">Adres:</span> {agreement.lesson_address}
    </div>
  {/if}

  {#if agreement.notes}
    <div class="text-sm text-gray-700">
      <span class="font-semibold">Not:</span> {agreement.notes}
    </div>
  {/if}

  {#if agreement.meeting_link}
    <div class="flex items-center justify-between gap-3 bg-blue-50 border border-blue-100 rounded-lg px-3 py-2">
      <div class="text-sm text-blue-800 font-semibold">Jitsi Linki hazır</div>
      <a
        class="text-sm font-semibold text-blue-700 underline"
        href={agreement.meeting_link}
        target="_blank"
        rel="noreferrer"
      >
        Dersi başlat
      </a>
    </div>
  {/if}

  <div class="flex flex-wrap gap-2">
    {#if canRespond}
      <button
        class="px-3 py-2 bg-green-600 text-white rounded-lg text-sm font-semibold hover:bg-green-700 transition disabled:opacity-50"
        on:click={() => respond('accepted')}
        disabled={responding}
      >Kabul Et</button>
      <button
        class="px-3 py-2 bg-red-600 text-white rounded-lg text-sm font-semibold hover:bg-red-700 transition disabled:opacity-50"
        on:click={() => respond('rejected')}
        disabled={responding}
      >Reddet</button>
    {/if}
    {#if canCancel}
      <button
        class="px-3 py-2 bg-gray-100 text-gray-800 rounded-lg text-sm font-semibold hover:bg-gray-200 transition disabled:opacity-50"
        on:click={() => respond('cancelled')}
        disabled={responding}
      >İptal Et</button>
    {/if}
  </div>

  {#if agreement.status === 'accepted'}
    <div class="border-t border-dashed border-gray-200 pt-3 space-y-2">
      <div class="text-sm font-semibold text-gray-900">Ders Saati Kaydet (Ödül takibi)</div>
      <div class="grid md:grid-cols-3 gap-2">
        <input
          type="number"
          min="0.5"
          step="0.5"
          placeholder="Saat"
          class="border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
          bind:value={hoursInput}
        />
        <input
          type="text"
          placeholder="Not (opsiyonel)"
          class="border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 md:col-span-2"
          bind:value={hoursNote}
        />
      </div>
      <div class="flex justify-end">
        <button
          class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 transition disabled:opacity-50"
          on:click={logHours}
          disabled={loggingHours}
        >
          {loggingHours ? 'Kaydediliyor...' : 'Saat Ekle'}
        </button>
      </div>
    </div>
  {/if}
</div>
