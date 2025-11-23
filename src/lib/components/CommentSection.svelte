<script>
  import { authStore as auth } from '$lib/stores/auth';
  import { api } from '$lib/utils/api';

  export let comments = [];
  export let postId;

  let newComment = '';
  let submitting = false;

  async function handleSubmit() {
    if (!newComment.trim()) return;
    
    submitting = true;
    const res = await api.post('/blog/comment.php', {
      postId,
      text: newComment,
      user: $auth.user?.full_name || 'Anonim'
    });
    
    if (res.success) {
      // Optimistically add comment
      comments = [...comments, {
        id: Date.now(),
        user: $auth.user?.full_name || 'Anonim',
        text: newComment,
        date: new Date().toISOString().split('T')[0]
      }];
      newComment = '';
    }
    submitting = false;
  }
</script>

<div class="mt-12 pt-8 border-t border-gray-200">
  <h3 class="text-2xl font-bold text-gray-900 mb-6">Yorumlar ({comments.length})</h3>

  <!-- Comment List -->
  <div class="space-y-6 mb-10">
    {#each comments as comment}
      <div class="bg-gray-50 p-4 rounded-xl">
        <div class="flex justify-between items-center mb-2">
          <span class="font-bold text-gray-900">{comment.user}</span>
          <span class="text-sm text-gray-500">{comment.date}</span>
        </div>
        <p class="text-gray-700">{comment.text}</p>
      </div>
    {:else}
      <p class="text-gray-500 italic">Henüz yorum yapılmamış. İlk yorumu siz yapın!</p>
    {/each}
  </div>

  <!-- Add Comment Form -->
  {#if $auth.isAuthenticated}
    <div class="bg-white border border-gray-200 rounded-xl p-6">
      <h4 class="font-bold text-gray-900 mb-4">Yorum Yap</h4>
      <textarea
        bind:value={newComment}
        rows="3"
        class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition mb-4"
        placeholder="Düşüncelerinizi paylaşın..."
      ></textarea>
      <button
        on:click={handleSubmit}
        disabled={submitting || !newComment.trim()}
        class="bg-blue-600 text-white font-bold px-6 py-2 rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
      >
        {submitting ? 'Gönderiliyor...' : 'Gönder'}
      </button>
    </div>
  {:else}
    <div class="bg-blue-50 rounded-xl p-6 text-center">
      <p class="text-gray-700 mb-4">Yorum yapabilmek için giriş yapmalısınız.</p>
      <a href="/giris" class="inline-block bg-blue-600 text-white font-bold px-6 py-2 rounded-lg hover:bg-blue-700 transition">
        Giriş Yap
      </a>
    </div>
  {/if}
</div>
