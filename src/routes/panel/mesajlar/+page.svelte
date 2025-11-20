<script>
  import { onMount } from 'svelte';
  import { authStore } from '$lib/stores/auth.js';
  import { api } from '$lib/utils/api.js';
  import { goto } from '$app/navigation';
  import { page } from '$app/stores';

  let conversations = [];
  let activeConversation = null;
  let messages = [];
  let newMessage = '';
  let loading = true;
  let sending = false;

  $: user = $authStore.user;

  onMount(async () => {
    if (!$authStore.isAuthenticated) {
      goto('/giris');
      return;
    }
    await loadConversations();
    
    // Check for teacher_id query param to start new chat
    const teacherId = $page.url.searchParams.get('teacher_id');
    if (teacherId) {
      // Logic to find or create conversation with this teacher
      // For mock purposes, we'll just select the first one if it matches or alert
      // In real app, you'd check if conversation exists, if not create new empty state
      alert('Yeni mesajlaşma başlatma özelliği demo modunda ilk konuşmayı açar.');
      if (conversations.length > 0) selectConversation(conversations[0]);
    } else if (conversations.length > 0) {
      selectConversation(conversations[0]);
    }
  });

  async function loadConversations() {
    try {
      const res = await api.get('/messages/list.php');
      conversations = res.data;
    } catch (e) {
      console.error(e);
    } finally {
      loading = false;
    }
  }

  async function selectConversation(conv) {
    activeConversation = conv;
    try {
      const res = await api.get('/messages/detail.php', { id: conv.id });
      messages = res.data;
      // Scroll to bottom
      setTimeout(() => {
        const chatContainer = document.getElementById('chat-container');
        if (chatContainer) chatContainer.scrollTop = chatContainer.scrollHeight;
      }, 0);
    } catch (e) {
      console.error(e);
    }
  }

  async function sendMessage() {
    if (!newMessage.trim() || !activeConversation) return;
    
    sending = true;
    try {
      const res = await api.post('/messages/send.php', {
        conversation_id: activeConversation.id,
        text: newMessage
      });
      
      messages = [...messages, res.data];
      newMessage = '';
      
      // Update conversation list preview
      const convIndex = conversations.findIndex(c => c.id === activeConversation.id);
      if (convIndex !== -1) {
        conversations[convIndex].last_message = res.data.text;
        conversations[convIndex].last_message_date = res.data.created_at;
      }
      
      setTimeout(() => {
        const chatContainer = document.getElementById('chat-container');
        if (chatContainer) chatContainer.scrollTop = chatContainer.scrollHeight;
      }, 0);
    } catch (e) {
      console.error(e);
    } finally {
      sending = false;
    }
  }
</script>

<svelte:head>
  <title>Mesajlar - DijitalMentor</title>
</svelte:head>

<div class="container mx-auto px-4 py-8 h-[calc(100vh-4rem)]">
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 h-full flex overflow-hidden">
    
    <!-- Sidebar -->
    <div class="w-full md:w-80 border-r border-gray-100 flex flex-col">
      <div class="p-4 border-b border-gray-100">
        <h2 class="text-xl font-bold text-gray-900">Mesajlar</h2>
      </div>
      
      <div class="flex-1 overflow-y-auto">
        {#if loading}
          <div class="p-4 text-center text-gray-500">Yükleniyor...</div>
        {:else if conversations.length === 0}
          <div class="p-4 text-center text-gray-500">Henüz mesajınız yok.</div>
        {:else}
          {#each conversations as conv}
            <button 
              class="w-full p-4 flex items-start gap-3 hover:bg-gray-50 transition text-left border-b border-gray-50 last:border-0 {activeConversation?.id === conv.id ? 'bg-blue-50/50' : ''}"
              on:click={() => selectConversation(conv)}
            >
              <img 
                src={conv.other_user.avatar_url} 
                alt={conv.other_user.full_name}
                class="w-12 h-12 rounded-full object-cover"
              />
              <div class="flex-1 min-w-0">
                <div class="flex justify-between items-baseline mb-1">
                  <h3 class="font-semibold text-gray-900 truncate">{conv.other_user.full_name}</h3>
                  <span class="text-xs text-gray-400 whitespace-nowrap ml-2">
                    {new Date(conv.last_message_date).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                  </span>
                </div>
                <p class="text-sm text-gray-500 truncate {conv.unread_count > 0 ? 'font-semibold text-gray-900' : ''}">
                  {conv.last_message}
                </p>
              </div>
            </button>
          {/each}
        {/if}
      </div>
    </div>

    <!-- Chat Area -->
    <div class="flex-1 flex flex-col bg-gray-50/30 hidden md:flex">
      {#if activeConversation}
        <!-- Chat Header -->
        <div class="p-4 bg-white border-b border-gray-100 flex items-center gap-3">
          <img 
            src={activeConversation.other_user.avatar_url} 
            alt={activeConversation.other_user.full_name}
            class="w-10 h-10 rounded-full object-cover"
          />
          <div>
            <h3 class="font-bold text-gray-900">{activeConversation.other_user.full_name}</h3>
            <span class="text-xs text-green-500 font-medium">Çevrimiçi</span>
          </div>
        </div>

        <!-- Messages -->
        <div id="chat-container" class="flex-1 overflow-y-auto p-4 space-y-4">
          {#each messages as msg}
            <div class="flex {msg.sender_id === user.id ? 'justify-end' : 'justify-start'}">
              <div class="max-w-[70%] rounded-2xl px-4 py-2 {msg.sender_id === user.id ? 'bg-blue-600 text-white rounded-br-none' : 'bg-white border border-gray-100 text-gray-800 rounded-bl-none'}">
                <p>{msg.text}</p>
                <div class="text-xs mt-1 opacity-70 text-right">
                  {new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                </div>
              </div>
            </div>
          {/each}
        </div>

        <!-- Input Area -->
        <div class="p-4 bg-white border-t border-gray-100">
          <form on:submit|preventDefault={sendMessage} class="flex gap-2">
            <input 
              type="text" 
              bind:value={newMessage}
              placeholder="Bir mesaj yazın..."
              class="flex-1 border border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition"
            />
            <button 
              type="submit" 
              class="bg-blue-600 text-white px-6 rounded-xl font-semibold hover:bg-blue-700 transition disabled:opacity-50"
              disabled={!newMessage.trim() || sending}
            >
              Gönder
            </button>
          </form>
        </div>
      {:else}
        <div class="flex-1 flex items-center justify-center text-gray-400 flex-col gap-4">
          <div class="text-6xl">💬</div>
          <p>Mesajlaşmaya başlamak için bir sohbet seçin.</p>
        </div>
      {/if}
    </div>
  </div>
</div>
