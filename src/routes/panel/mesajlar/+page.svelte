<script>
  import { onMount } from 'svelte';
  import { authStore } from '$lib/stores/auth.js';
  import { api } from '$lib/utils/api.js';
  import { goto } from '$app/navigation';
  import { page } from '$app/stores';
  import AgreementForm from '$lib/components/AgreementForm.svelte';
  import AgreementCard from '$lib/components/AgreementCard.svelte';
  import LessonAgreementForm from '$lib/components/LessonAgreementForm.svelte';
  import { toast } from '$lib/stores/toast.js';
  import { browser } from '$app/environment';

  let conversations = [];
  let activeConversation = null;
  let messages = [];
  let newMessage = '';
  let loading = true;
  let sending = false;
  let agreements = [];
  let agreementsLoading = false;
  let showAgreementForm = false;
  let lessonAgreements = [];
  let lessonAgreementsLoading = false;
  let showLessonAgreementForm = false;
  let subjects = [];
  let subjectsLoading = false;
  let isMobile = false;
  let showMobileList = false;

  $: user = $authStore.user;

  let refreshInterval;

  const cleanupFns = [];

  onMount(() => {
    (async () => {
      if (!$authStore.isAuthenticated) {
        goto('/giris');
        return;
      }

      if (browser) {
        const mql = window.matchMedia('(max-width: 767px)');
        const handleChange = (event) => {
          isMobile = event.matches;
          if (!isMobile) {
            showMobileList = false;
          } else if (!activeConversation) {
            showMobileList = true;
          }
        };
        handleChange(mql);
        mql.addEventListener('change', handleChange);
        cleanupFns.push(() => mql.removeEventListener('change', handleChange));
      }

      await loadConversations();

      // Check for conversation_id query param to select existing conversation
      const conversationId = $page.url.searchParams.get('conversation_id');
      if (conversationId) {
        const idNum = parseInt(conversationId);
        console.log('Looking for conversation ID:', idNum);
        console.log('Available conversations:', conversations);
        const conv = conversations.find(c => c.id === idNum);
        console.log('Found conversation:', conv);
        if (conv) {
          await selectConversation(conv);
        } else if (conversations.length > 0) {
          console.warn('Conversation not found, selecting first one');
          await selectConversation(conversations[0]);
        } else {
          console.error('No conversations available');
        }
      }
      // Check for teacher_id query param to start new chat
      else {
        const teacherId = $page.url.searchParams.get('teacher_id');
        if (teacherId) {
          const idNum = parseInt(teacherId);
          // Öğretmenler diğer öğretmenlerle mesajlaşamaz
          if ($authStore.user?.role === 'student') {
            toast.error('Öğretmenler diğer öğretmenlerle mesajlaşamaz');
          } else {
            await startNewConversation(idNum);
          }
        } else if (conversations.length > 0) {
          selectConversation(conversations[0]);
        }
      }

      loadSubjects();

      // Auto-refresh every 30 seconds
      refreshInterval = setInterval(() => {
        if (activeConversation) {
          loadMessages(activeConversation.id, true);
          loadAgreements(activeConversation.id, true);
        }
        loadConversations();
      }, 30000);
    })();

    return () => {
      if (refreshInterval) clearInterval(refreshInterval);
      cleanupFns.forEach(fn => fn());
    };
  });

  async function loadConversations() {
    try {
      const res = await api.get('/messages/list.php');
      conversations = res.data;
      console.log('Loaded conversations:', conversations);
    } catch (e) {
      console.error('Error loading conversations:', e);
    } finally {
      loading = false;
    }
  }

  async function selectConversation(conv) {
    activeConversation = conv;
    if (isMobile) {
      showMobileList = false;
    }
    await loadMessages(conv.id);
    await loadAgreements(conv.id);
    await loadLessonAgreements();
  }

  async function loadMessages(conversationId, silent = false) {
    try {
      const res = await api.get(`/messages/detail.php?conversation_id=${conversationId}`);
      messages = res.data.messages.reverse(); // API returns DESC, we want ASC for display

      // Scroll to bottom
      setTimeout(() => {
        const chatContainer = document.getElementById('chat-container');
        if (chatContainer) chatContainer.scrollTop = chatContainer.scrollHeight;
      }, 0);
    } catch (e) {
      if (!silent) console.error(e);
    }
  }

  async function loadSubjects() {
    if (subjectsLoading || subjects.length) return;
    subjectsLoading = true;
    try {
      const res = await api.get('/subjects/list.php');
      subjects = res.data || [];
    } catch (e) {
      console.error(e);
    } finally {
      subjectsLoading = false;
    }
  }

  async function loadAgreements(conversationId, silent = false) {
    if (!conversationId) return;
    agreementsLoading = true;
    try {
      const res = await api.get(`/agreements/list.php?conversation_id=${conversationId}`);
      agreements = res.data || [];
    } catch (e) {
      if (!silent) {
        console.error(e);
        toast.error('Onay formları yüklenemedi');
      }
    } finally {
      agreementsLoading = false;
    }
  }

  async function startNewConversation(otherUserId) {
    try {
      const res = await api.post('/messages/start.php', { other_user_id: otherUserId });

      // Reload conversations to include the new one
      await loadConversations();

      // Find and select the conversation
      const conv = conversations.find(c => c.id === res.data.conversation_id);
      if (conv) {
        selectConversation(conv);
      }
    } catch (e) {
      console.error(e);
      alert('Mesajlaşma başlatılamadı. Lütfen tekrar deneyin.');
    }
  }

  async function sendMessage() {
    if (!newMessage.trim() || !activeConversation) return;

    sending = true;
    const messageToSend = newMessage;
    newMessage = ''; // Clear input immediately for better UX

    try {
      const res = await api.post('/messages/send.php', {
        conversation_id: activeConversation.id,
        message_text: messageToSend
      });

      messages = [...messages, res.data];

      // Update conversation list preview
      const convIndex = conversations.findIndex(c => c.id === activeConversation.id);
      if (convIndex !== -1) {
        conversations[convIndex].last_message = res.data.message_text;
        conversations[convIndex].last_message_at = res.data.created_at;
        // Move conversation to top
        const conv = conversations.splice(convIndex, 1)[0];
        conversations = [conv, ...conversations];
      }

      setTimeout(() => {
        const chatContainer = document.getElementById('chat-container');
        if (chatContainer) chatContainer.scrollTop = chatContainer.scrollHeight;
      }, 0);
    } catch (e) {
      console.error(e);
      newMessage = messageToSend; // Restore message on error
      alert('Mesaj gönderilemedi. Lütfen tekrar deneyin.');
    } finally {
      sending = false;
    }
  }

  async function loadLessonAgreements() {
    if (!activeConversation) return;
    lessonAgreementsLoading = true;
    try {
      const res = await api.get('/lessons/agreements.php');
      // Filter agreements for current conversation
      lessonAgreements = (res.data || []).filter(agreement => {
        const teacherId = user?.role === 'student' ? user.id : activeConversation.other_user.id;
        const parentId = user?.role === 'parent' ? user.id : activeConversation.other_user.id;
        return agreement.teacher_id === teacherId && agreement.parent_id === parentId;
      });
    } catch (e) {
      console.error('Error loading lesson agreements:', e);
    } finally {
      lessonAgreementsLoading = false;
    }
  }

  function handleAgreementSuccess() {
    showAgreementForm = false;
    if (activeConversation) {
      loadAgreements(activeConversation.id);
    }
  }

  function handleAgreementResponded() {
    if (activeConversation) {
      loadAgreements(activeConversation.id);
    }
  }

  function handleLessonAgreementSuccess() {
    showLessonAgreementForm = false;
    loadLessonAgreements();
  }

  function handleHoursLogged(event) {
    const rewards = event.detail?.new_rewards || [];
    if (rewards.length) {
      toast.success(`Tebrikler! ${rewards.length} yeni ödül açıldı.`);
    }
  }
</script>

<svelte:head>
  <title>Mesajlar - DijitalMentor</title>
</svelte:head>

<div class="container mx-auto px-4 py-8 h-[calc(100vh-4rem)]">
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 h-full flex overflow-hidden">
    
    <!-- Sidebar -->
    <div class={`w-full md:w-80 border-r border-gray-100 flex-col ${isMobile && !showMobileList ? 'hidden' : 'flex'}`}>
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
              <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-lg">
                {conv.other_user.name.charAt(0).toUpperCase()}
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex justify-between items-baseline mb-1">
                  <h3 class="font-semibold text-gray-900 truncate">{conv.other_user.name}</h3>
                  {#if conv.last_message_at}
                    <span class="text-xs text-gray-400 whitespace-nowrap ml-2">
                      {new Date(conv.last_message_at).toLocaleTimeString('tr-TR', {hour: '2-digit', minute:'2-digit'})}
                    </span>
                  {/if}
                </div>
                <p class="text-sm text-gray-500 truncate {conv.unread_count > 0 ? 'font-semibold text-gray-900' : ''}">
                  {conv.last_message || 'Henüz mesaj yok'}
                </p>
              </div>
              {#if conv.unread_count > 0}
                <div class="bg-blue-600 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center flex-shrink-0">
                  {conv.unread_count}
                </div>
              {/if}
            </button>
          {/each}
        {/if}
      </div>
    </div>

    <!-- Chat Area -->
    <div class={`flex-1 bg-gray-50/30 flex-col ${isMobile && showMobileList ? 'hidden' : 'flex'}`}>
      {#if activeConversation}
        <!-- Chat Header -->
        <div class="p-4 bg-white border-b border-gray-100 flex items-center gap-3">
          {#if isMobile}
            <button
              class="mr-2 text-blue-600 font-semibold"
              on:click={() => {
                showMobileList = true;
              }}
            >
              ← Mesajlar
            </button>
          {/if}
          <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold">
            {activeConversation.other_user.name.charAt(0).toUpperCase()}
          </div>
          <div>
            <h3 class="font-bold text-gray-900">{activeConversation.other_user.name}</h3>
            <span class="text-xs text-gray-500">{activeConversation.other_user.role === 'student' ? 'Öğretmen' : 'Veli'}</span>
          </div>
        </div>

        <!-- Messages -->
        <div id="chat-container" class="flex-1 overflow-y-auto p-4 space-y-4">
          {#if messages.length === 0}
            <div class="flex items-center justify-center h-full text-gray-400">
              <p>Henüz mesaj yok. İlk mesajı gönderin!</p>
            </div>
          {:else}
            {#each messages as msg}
              <div class="flex {msg.is_mine ? 'justify-end' : 'justify-start'}">
                <div class="max-w-[70%] rounded-2xl px-4 py-2 {msg.is_mine ? 'bg-blue-600 text-white rounded-br-none' : 'bg-white border border-gray-100 text-gray-800 rounded-bl-none'}">
                  <p>{msg.message_text}</p>
                  <div class="text-xs mt-1 opacity-70 text-right">
                    {new Date(msg.created_at).toLocaleTimeString('tr-TR', {hour: '2-digit', minute:'2-digit'})}
                  </div>
                </div>
              </div>
            {/each}
          {/if}
        </div>

        <!-- Lesson Agreements Section -->
        <div class="bg-white border-t border-gray-100 px-4 py-3 space-y-3">
          <div class="border border-green-100 rounded-xl overflow-hidden">
            <button
              class="w-full flex items-center justify-between px-4 py-3 text-left bg-green-50 hover:bg-green-100 transition"
              on:click={() => showLessonAgreementForm = !showLessonAgreementForm}
            >
              <div>
                <h4 class="text-sm font-semibold text-gray-900">Ders Anlaşması Oluştur</h4>
                <p class="text-xs text-gray-600">Yer, saat, fiyat konusunda anlaşın</p>
              </div>
              <svg class={`w-5 h-5 text-green-600 transition-transform ${showLessonAgreementForm ? 'rotate-180' : ''}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div
              class={`overflow-hidden transition-all duration-300 ${showLessonAgreementForm ? 'max-h-[1000px] opacity-100' : 'max-h-0 opacity-0'}`}
            >
              {#if showLessonAgreementForm}
                <div class="p-4">
                  <LessonAgreementForm
                    conversationId={activeConversation.id}
                    teacherId={user?.role === 'student' ? user.id : activeConversation.other_user.id}
                    parentId={user?.role === 'parent' ? user.id : activeConversation.other_user.id}
                    subjects={subjects}
                    on:success={handleLessonAgreementSuccess}
                  />
                </div>
              {/if}
            </div>
          </div>

          {#if lessonAgreementsLoading}
            <div class="text-sm text-gray-500">Anlaşmalar yükleniyor...</div>
          {:else if lessonAgreements.length > 0}
            <div class="space-y-2">
              <h5 class="text-sm font-semibold text-gray-900">Ders Anlaşmaları</h5>
              {#each lessonAgreements as agreement}
                <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                  <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                      <span class="text-lg">{agreement.subject_icon}</span>
                      <span class="font-semibold text-gray-900">{agreement.subject_name}</span>
                    </div>
                    <span class="text-xs px-2 py-1 rounded-full {
                      agreement.status === 'confirmed' ? 'bg-green-100 text-green-700' :
                      agreement.status === 'completed' ? 'bg-blue-100 text-blue-700' :
                      agreement.status === 'cancelled' ? 'bg-red-100 text-red-700' :
                      'bg-yellow-100 text-yellow-700'
                    }">
                      {agreement.status === 'confirmed' ? 'Onaylandı' :
                       agreement.status === 'completed' ? 'Tamamlandı' :
                       agreement.status === 'cancelled' ? 'İptal' :
                       'Beklemede'}
                    </span>
                  </div>
                  <div class="text-xs text-gray-600 space-y-1">
                    <p>📅 {new Date(agreement.lesson_date).toLocaleDateString('tr-TR')} {agreement.lesson_time ? '• ' + agreement.lesson_time : ''}</p>
                    <p>💰 €{agreement.agreed_price}/saat • {agreement.agreed_duration} saat</p>
                    <p>📍 {agreement.location === 'online' ? 'Online' : agreement.location === 'in_person' ? 'Yüz Yüze' : 'Adres'}</p>
                  </div>
                </div>
              {/each}
            </div>
          {/if}
        </div>

        <!-- Agreements (Existing Onay Formu) -->
        <div class="bg-white border-t border-gray-100 px-4 py-3 space-y-3">
          <div class="border border-blue-100 rounded-xl overflow-hidden">
            <button
              class="w-full flex items-center justify-between px-4 py-3 text-left bg-blue-50 hover:bg-blue-100 transition"
              on:click={() => showAgreementForm = !showAgreementForm}
            >
              <div>
                <h4 class="text-sm font-semibold text-gray-900">Onay Formu Gönder</h4>
                <p class="text-xs text-gray-600">Ders detaylarını paylaşın, Jitsi linki oluşturun</p>
              </div>
              <svg class={`w-5 h-5 text-blue-600 transition-transform ${showAgreementForm ? 'rotate-180' : ''}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div
              class={`overflow-hidden transition-all duration-300 ${showAgreementForm ? 'max-h-[1000px] opacity-100' : 'max-h-0 opacity-0'}`}
            >
              {#if showAgreementForm}
                <div class="p-4">
                  <AgreementForm
                    conversationId={activeConversation.id}
                    recipientId={activeConversation.other_user.id}
                    subjects={subjects}
                    on:success={handleAgreementSuccess}
                  />
                </div>
              {/if}
            </div>
          </div>

          {#if agreementsLoading}
            <div class="text-sm text-gray-500">Onay formları yükleniyor...</div>
          {:else if agreements.length === 0}
            <div class="text-sm text-gray-500">Henüz onay formu yok.</div>
          {:else}
            <div class="grid gap-3">
              {#each agreements as agreement}
                <AgreementCard
                  agreement={agreement}
                  on:responded={handleAgreementResponded}
                  on:hoursLogged={handleHoursLogged}
                />
              {/each}
            </div>
          {/if}
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
