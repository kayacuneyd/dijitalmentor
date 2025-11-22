<script>
  import { onMount } from 'svelte';
  import { api } from '$lib/utils/api.js';
  import { toast } from '$lib/stores/toast.js';

  let loading = true;
  let summary = {
    total_hours: 0,
    rewards: [],
    next_milestone: null
  };

  async function loadRewards() {
    loading = true;
    try {
      const res = await api.get('/rewards/list.php');
      summary = res.data;
    } catch (err) {
      console.error(err);
      toast.error(err.message || 'Ödüller alınamadı');
    } finally {
      loading = false;
    }
  }

  async function claimReward(id) {
    try {
      await api.post('/rewards/claim.php', { reward_id: id });
      toast.success('Ödül talep edildi');
      await loadRewards();
    } catch (err) {
      console.error(err);
      toast.error(err.message || 'Ödül talep edilemedi');
    }
  }

  onMount(loadRewards);
</script>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-6">
  <div class="flex items-center justify-between">
    <div>
      <h3 class="text-lg font-bold text-gray-900">Ödül ve Saat Takibi</h3>
      <p class="text-sm text-gray-500">Toplam ders saatinizi ve kazandığınız ödülleri takip edin.</p>
    </div>
    <div class="px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-sm font-semibold">
      Toplam: {summary.total_hours?.toFixed ? summary.total_hours.toFixed(1) : summary.total_hours} saat
    </div>
  </div>

  {#if loading}
    <div class="text-center text-gray-500 py-6">Yükleniyor...</div>
  {:else}
    <div class="space-y-4">
      <!-- Progress -->
      <div class="p-4 bg-blue-50 border border-blue-100 rounded-xl">
        {#if summary.next_milestone}
          <div class="flex justify-between items-center text-sm text-blue-900 font-semibold">
            <span>Sonraki hedef: {summary.next_milestone.hours_required} saat</span>
            <span>{summary.total_hours.toFixed(1)} / {summary.next_milestone.hours_required} saat</span>
          </div>
          <div class="h-2 bg-white rounded-full border border-blue-100 mt-2 overflow-hidden">
            <div
              class="h-full bg-blue-600"
              style={`width: ${Math.min(100, (summary.total_hours / summary.next_milestone.hours_required) * 100)}%`}
            ></div>
          </div>
          <div class="text-sm text-blue-800 mt-2">
            Ödül: {summary.next_milestone.reward_title} ({summary.next_milestone.reward_description})
          </div>
        {:else}
          <div class="text-sm text-blue-900 font-semibold">Tüm hedefler tamamlandı 🎉</div>
        {/if}
      </div>

      <!-- Rewards list -->
      {#if summary.rewards?.length}
        <div class="space-y-3">
          {#each summary.rewards as reward}
            <div class="border border-gray-200 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
              <div>
                <div class="text-sm text-gray-500">Hedef: {reward.hours_milestone} saat</div>
                <div class="text-base font-semibold text-gray-900">{reward.reward_title}</div>
                <div class="text-sm text-gray-600">{reward.reward_description}</div>
                <div class="text-xs text-gray-500 mt-1">Veriliş: {new Date(reward.awarded_at).toLocaleString('tr-TR')}</div>
              </div>
              <div class="flex items-center gap-2">
                {#if reward.reward_value}
                  <span class="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-xs font-semibold">
                    €{reward.reward_value}
                  </span>
                {/if}
                <button
                  class={`px-3 py-2 rounded-lg text-sm font-semibold border transition ${
                    reward.is_claimed
                      ? 'bg-green-50 text-green-700 border-green-100 cursor-default'
                      : 'bg-blue-600 text-white border-blue-600 hover:bg-blue-700'
                  }`}
                  on:click={() => !reward.is_claimed && claimReward(reward.id)}
                  disabled={reward.is_claimed}
                >
                  {reward.is_claimed ? 'Talep edildi' : 'Ödülü Al'}
                </button>
              </div>
            </div>
          {/each}
        </div>
      {:else}
        <div class="text-sm text-gray-500">Henüz ödül yok. Ders saatlerini ekledikçe ödüller açılır.</div>
      {/if}
    </div>
  {/if}
</div>
