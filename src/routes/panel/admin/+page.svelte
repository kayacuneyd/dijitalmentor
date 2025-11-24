<script>
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import { authStore } from '$lib/stores/auth.js';
  import { api } from '$lib/utils/api.js';
  import { toast } from '$lib/stores/toast.js';
  import MarkdownEditor from '$lib/components/MarkdownEditor.svelte';
  import PodcastManagement from '$lib/components/admin/PodcastManagement.svelte';
  import { marked } from 'marked';

  let activeTab = 'teachers';

  let teacherStatus = 'pending';
  let teachers = [];
  let teacherLoading = false;

  let recentMessages = [];
  let messagesLoading = false;
  let selectedConversation = null;
  let conversationLoading = false;

  let supportMessages = [];
  let supportLoading = false;

  let blogPosts = [];
  let blogLoading = false;
  let blogForm = {
    id: null,
    slug: '',
    title: '',
    excerpt: '',
    content: '',
    content_markdown: '',
    author: '',
    image: '',
    is_published: 1
  };
  let announcements = [];
  let annLoading = false;
  let announcementForm = {
    id: null,
    slug: '',
    title: '',
    body: '',
    award_month: '',
    award_name: '',
    is_published: 0,
    winners: []
  };

  let pendingReviews = [];
  let reviewsLoading = false;

  let rewardOverview = {
    parent_hours: 0,
    teacher_hours: 0,
    parent_count: 0,
    teacher_count: 0
  };
  let rewardHistory = [];
  let rewardLoading = false;
  let rewardForm = {
    user_id: '',
    hours: '',
    notes: ''
  };

  // Teacher edit modal
  let selectedTeacher = null;
  let teacherEditForm = null;
  let teacherEditLoading = false;
  let teacherEditSaving = false;
  let showTeacherEditModal = false;
  let allSubjects = [];

  let initialized = false;

  $: authState = $authStore;

  $: if (!authState.loading) {
    if (!authState.isAuthenticated) {
      goto('/giris');
    } else if (authState.user?.role !== 'admin') {
      goto('/panel');
    }
  }

  onMount(async () => {
    if (!authState.isAuthenticated || authState.user?.role !== 'admin') {
      return;
    }
    await Promise.all([
      loadTeachers(),
      loadRecentMessages(),
      loadSupportMessages(),
      loadBlogPosts(),
      loadAnnouncements(),
      loadPendingReviews(),
      loadRewards(),
      loadSubjects()
    ]);
    initialized = true;
  });

  async function loadTeachers() {
    teacherLoading = true;
    try {
      const res = await api.get('/admin/teachers/list.php', { status: teacherStatus });
      teachers = res.data || [];
    } catch (err) {
      toast.error(err.message || 'Öğretmenler yüklenemedi');
    } finally {
      teacherLoading = false;
    }
  }

  async function updateTeacherStatus(userId, status) {
    try {
      await api.post('/admin/teachers/update.php', {
        user_id: userId,
        approval_status: status
      });
      toast.success('Durum güncellendi');
      loadTeachers();
    } catch (err) {
      toast.error(err.message || 'Durum güncellenemedi');
    }
  }

  async function loadRecentMessages() {
    messagesLoading = true;
    try {
      const res = await api.get('/admin/messages/recent.php', { limit: 100 });
      recentMessages = res.data || [];
    } catch (err) {
      toast.error(err.message || 'Mesajlar getirilemedi');
    } finally {
      messagesLoading = false;
    }
  }

  async function viewConversation(conversationId) {
    conversationLoading = true;
    try {
      const res = await api.get('/admin/messages/conversation.php', { conversation_id: conversationId });
      selectedConversation = res.data;
    } catch (err) {
      toast.error(err.message || 'Konuşma getirilemedi');
    } finally {
      conversationLoading = false;
    }
  }

  async function loadSubjects() {
    try {
      const res = await api.get('/subjects/list.php');
      allSubjects = res.data || [];
    } catch (err) {
      console.error('Dersler yüklenemedi:', err);
    }
  }

  async function openTeacherEdit(teacherId) {
    teacherEditLoading = true;
    showTeacherEditModal = true;
    try {
      const res = await api.get('/admin/teachers/detail.php', { id: teacherId });
      selectedTeacher = res.data;
      
      // Form verisini hazırla
      teacherEditForm = {
        user_id: selectedTeacher.id,
        full_name: selectedTeacher.full_name || '',
        email: selectedTeacher.email || '',
        phone: selectedTeacher.phone || '',
        approval_status: selectedTeacher.approval_status || 'pending',
        is_active: selectedTeacher.is_active ? true : false,
        is_verified: selectedTeacher.is_verified ? true : false,
        university: selectedTeacher.university || '',
        department: selectedTeacher.department || '',
        graduation_year: selectedTeacher.graduation_year || '',
        bio: selectedTeacher.bio || '',
        city: selectedTeacher.city || '',
        zip_code: selectedTeacher.zip_code || '',
        hourly_rate: selectedTeacher.hourly_rate || 20,
        experience_years: selectedTeacher.experience_years || 0,
        rating_avg: selectedTeacher.rating_avg || 0,
        review_count: selectedTeacher.review_count || 0,
        subjects: (selectedTeacher.subjects || []).map(s => ({
          id: s.id,
          proficiency_level: s.proficiency_level || 'intermediate'
        }))
      };
    } catch (err) {
      toast.error(err.message || 'Öğretmen bilgileri yüklenemedi');
      showTeacherEditModal = false;
    } finally {
      teacherEditLoading = false;
    }
  }

  function closeTeacherEdit() {
    showTeacherEditModal = false;
    selectedTeacher = null;
    teacherEditForm = null;
  }

  function toggleSubject(subjectId) {
    if (!teacherEditForm) return;
    const existingIndex = teacherEditForm.subjects.findIndex(s => s.id === subjectId);
    if (existingIndex >= 0) {
      teacherEditForm.subjects.splice(existingIndex, 1);
    } else {
      teacherEditForm.subjects.push({
        id: subjectId,
        proficiency_level: 'intermediate'
      });
    }
    teacherEditForm = { ...teacherEditForm }; // Trigger reactivity
  }

  function updateSubjectLevel(subjectId, event) {
    if (!teacherEditForm) return;
    const level = event.currentTarget?.value || 'intermediate';
    const subject = teacherEditForm.subjects.find(s => s.id === subjectId);
    if (subject) {
      subject.proficiency_level = level;
      teacherEditForm = { ...teacherEditForm };
    }
  }

  async function saveTeacherEdit() {
    if (!teacherEditForm) return;
    teacherEditSaving = true;
    try {
      // Convert boolean to number for API
      const formData = {
        ...teacherEditForm,
        is_active: teacherEditForm.is_active ? 1 : 0,
        is_verified: teacherEditForm.is_verified ? 1 : 0
      };
      await api.post('/admin/teachers/edit.php', formData);
      toast.success('Öğretmen bilgileri güncellendi');
      closeTeacherEdit();
      loadTeachers();
    } catch (err) {
      toast.error(err.message || 'Güncelleme başarısız');
    } finally {
      teacherEditSaving = false;
    }
  }

  async function toggleUserActive(userId, isActive) {
    try {
      await api.post('/admin/users/toggle_active.php', {
        user_id: userId,
        is_active: isActive ? 1 : 0
      });
      toast.success('Kullanıcı durumu güncellendi');
      loadRecentMessages();
      loadTeachers();
    } catch (err) {
      toast.error(err.message || 'Kullanıcı güncellenemedi');
    }
  }

  async function loadSupportMessages(status = null) {
    supportLoading = true;
    try {
      const params = status ? { status } : {};
      const res = await api.get('/admin/support/messages.php', params);
      supportMessages = res.data || [];
    } catch (err) {
      toast.error(err.message || 'Destek mesajları getirilemedi');
    } finally {
      supportLoading = false;
    }
  }

  async function updateSupport(item, newStatus) {
    try {
      await api.post('/admin/support/update.php', {
        id: item.id,
        status: newStatus,
        admin_notes: item.admin_notes || ''
      });
      toast.success('Mesaj güncellendi');
      loadSupportMessages(activeSupportFilter || null);
    } catch (err) {
      toast.error(err.message || 'Mesaj güncellenemedi');
    }
  }

let activeSupportFilter = '';

  async function loadBlogPosts() {
    blogLoading = true;
    try {
      const res = await api.get('/admin/blog/list.php');
      blogPosts = res.data || [];
    } catch (err) {
      toast.error(err.message || 'Blog yazıları getirilemedi');
    } finally {
      blogLoading = false;
    }
  }

  function htmlToMarkdown(html = '') {
    if (!html) return '';
    return html
      .replace(/<\/p>\s*<p>/gi, '\n\n')
      .replace(/<br\s*\/?>/gi, '\n')
      .replace(/<\/?(strong|b)>/gi, '**')
      .replace(/<\/?(em|i)>/gi, '*')
      .replace(/<\/?h1>/gi, '\n# ')
      .replace(/<\/?h2>/gi, '\n## ')
      .replace(/<\/?h3>/gi, '\n### ')
      .replace(/<\/?ul>/gi, '\n')
      .replace(/<li>(.*?)<\/li>/gi, (_, item) => `\n- ${item.trim()}`)
      .replace(/<\/?ol>/gi, '\n')
      .replace(/<!--.*?-->/g, '')
      .replace(/<\/?[^>]+>/g, '')
      .replace(/&nbsp;/gi, ' ')
      .replace(/&amp;/gi, '&')
      .trim();
  }

  function editPost(post) {
    blogForm = {
      id: post.id,
      slug: post.slug,
      title: post.title,
      excerpt: post.excerpt,
      content: post.content || '',
      content_markdown: post.content_markdown || htmlToMarkdown(post.content || ''),
      author: post.author || '',
      image: post.image || '',
      is_published: post.is_published ? 1 : 0
    };
    activeTab = 'blog';
  }

  function resetBlogForm() {
    blogForm = {
      id: null,
      slug: '',
      title: '',
      excerpt: '',
      content: '',
      content_markdown: '',
      author: '',
      image: '',
      is_published: 1
    };
  }

  async function saveBlogPost() {
    try {
      const payload = {
        ...blogForm,
        content: blogForm.content_markdown
          ? marked.parse(blogForm.content_markdown)
          : blogForm.content
      };

      await api.post('/admin/blog/save.php', payload);
      toast.success('Yazı kaydedildi');
      resetBlogForm();
      loadBlogPosts();
    } catch (err) {
      toast.error(err.message || 'Yazı kaydedilemedi');
    }
  }

  // Announcements
  async function loadAnnouncements() {
    annLoading = true;
    try {
      const res = await api.get('/admin/announcements/list.php');
      announcements = res.data || [];
    } catch (err) {
      toast.error(err.message || 'Duyurular getirilemedi');
    } finally {
      annLoading = false;
    }
  }

  function editAnnouncement(item) {
    announcementForm = {
      id: item.id,
      slug: item.slug,
      title: item.title,
      body: item.body || '',
      award_month: item.award_month || '',
      award_name: item.award_name || '',
      is_published: item.is_published ? 1 : 0,
      winners: item.winners || []
    };
    activeTab = 'announcements';
  }

  function resetAnnouncement() {
    announcementForm = {
      id: null,
      slug: '',
      title: '',
      body: '',
      award_month: '',
      award_name: '',
      is_published: 0,
      winners: []
    };
  }

  function addWinner() {
    announcementForm = {
      ...announcementForm,
      winners: [...announcementForm.winners, { user_id: '', rank: (announcementForm.winners?.length || 0) + 1, rationale: '' }]
    };
  }

  function removeWinner(idx) {
    announcementForm = {
      ...announcementForm,
      winners: announcementForm.winners.filter((_, i) => i !== idx)
    };
  }

  async function saveAnnouncement() {
    try {
      await api.post('/admin/announcements/save.php', announcementForm);
      toast.success('Duyuru kaydedildi');
      resetAnnouncement();
      loadAnnouncements();
    } catch (err) {
      toast.error(err.message || 'Duyuru kaydedilemedi');
    }
  }

  async function deleteAnnouncement(id) {
    if (!confirm('Bu duyuruyu silmek istediğinize emin misiniz?')) return;
    try {
      await api.post('/admin/announcements/delete.php', { id });
      toast.success('Duyuru silindi');
      loadAnnouncements();
    } catch (err) {
      toast.error(err.message || 'Duyuru silinemedi');
    }
  }

  // Reviews moderation
  async function loadPendingReviews() {
    reviewsLoading = true;
    try {
      const res = await api.get('/admin/reviews/list.php', { status: 'pending', limit: 200 });
      pendingReviews = res.data || [];
    } catch (err) {
      toast.error(err.message || 'Yorumlar getirilemedi');
    } finally {
      reviewsLoading = false;
    }
  }

  async function moderateReview(id, status) {
    try {
      await api.post('/admin/reviews/moderate.php', { id, status });
      toast.success('Güncellendi');
      loadPendingReviews();
    } catch (err) {
      toast.error(err.message || 'Güncellenemedi');
    }
  }

  async function deleteBlogPost(id) {
    if (!confirm('Bu yazıyı silmek istediğinize emin misiniz?')) return;
    try {
      await api.post('/admin/blog/delete.php', { id });
      toast.success('Yazı silindi');
      loadBlogPosts();
    } catch (err) {
      toast.error(err.message || 'Yazı silinemedi');
    }
  }

  async function loadRewards() {
    rewardLoading = true;
    try {
      const res = await api.get('/admin/rewards/overview.php');
      rewardOverview = res.data?.overview || rewardOverview;
      rewardHistory = res.data?.history || [];
    } catch (err) {
      toast.error(err.message || 'Ödül verileri getirilemedi');
    } finally {
      rewardLoading = false;
    }
  }

  async function addManualHours() {
    if (!rewardForm.user_id || !rewardForm.hours) {
      toast.error('Kullanıcı ve saat zorunludur');
      return;
    }
    try {
      await api.post('/admin/rewards/adjust_hours.php', {
        user_id: Number(rewardForm.user_id),
        hours: Number(rewardForm.hours),
        notes: rewardForm.notes
      });
      toast.success('Saat kaydedildi');
      rewardForm = { user_id: '', hours: '', notes: '' };
      loadRewards();
    } catch (err) {
      toast.error(err.message || 'Saat kaydedilemedi');
    }
  }
</script>

<svelte:head>
  <title>Admin Panel - DijitalMentor</title>
</svelte:head>

{#if !initialized}
  <div class="container mx-auto px-4 py-12">
    <div class="flex justify-center py-12">
      <div class="w-12 h-12 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
    </div>
  </div>
{:else}
  <div class="container mx-auto px-4 py-8 space-y-8">
    <div>
      <h1 class="text-3xl font-bold text-gray-900 mb-2">Admin Paneli</h1>
      <p class="text-gray-600">Mentor onayı, mesaj denetimi, destek ve içerik yönetimi</p>
    </div>

    <div class="flex flex-wrap gap-2 border-b border-gray-200 pb-2">
      {#each [
        { id: 'teachers', label: 'Öğretmen Onayları' },
        { id: 'messages', label: 'Mesaj Denetimi' },
        { id: 'support', label: 'Destek Kutusu' },
        { id: 'reviews', label: 'Yorum Moderasyon' },
        { id: 'announcements', label: 'Duyurular & Ayın Ödülü' },
        { id: 'blog', label: 'Blog Yönetimi' },
        { id: 'podcast', label: '🎙️ Podcast Yönetimi' },
        { id: 'rewards', label: 'Ödül & Saat Takibi' }
      ] as tab}
        <button
          class={`px-4 py-2 rounded-full text-sm font-semibold transition ${activeTab === tab.id ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'}`}
          on:click={() => activeTab = tab.id}
        >
          {tab.label}
        </button>
      {/each}
    </div>

    {#if activeTab === 'teachers'}
      <section class="space-y-4">
        <div class="flex items-center justify-between flex-wrap gap-4">
          <h2 class="text-2xl font-semibold text-gray-900">Öğretmen Onayları</h2>
          <div class="flex items-center gap-2">
            <label class="text-sm text-gray-600" for="teacher-status">Durum:</label>
            <select
              id="teacher-status"
              bind:value={teacherStatus}
              on:change={loadTeachers}
              class="border rounded-lg px-3 py-1 text-sm"
            >
              <option value="pending">Beklemede</option>
              <option value="approved">Onaylı</option>
              <option value="rejected">Reddedildi</option>
            </select>
          </div>
        </div>

        {#if teacherLoading}
          <div class="flex justify-center py-12">
            <div class="w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
          </div>
        {:else}
          <div class="overflow-x-auto bg-white rounded-2xl border border-gray-100">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
              <thead class="bg-gray-50 text-gray-600">
                <tr>
                  <th class="px-4 py-3 text-left font-semibold">Ad Soyad</th>
                  <th class="px-4 py-3 text-left font-semibold">İletişim</th>
                  <th class="px-4 py-3 text-left font-semibold">Üniversite</th>
                  <th class="px-4 py-3 text-center font-semibold">Saatlik Ücret</th>
                  <th class="px-4 py-3 text-center font-semibold">Durum</th>
                  <th class="px-4 py-3 text-center font-semibold">İşlemler</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                {#each teachers as teacher}
                  <tr>
                    <td class="px-4 py-3">
                      <div class="font-semibold text-gray-900">{teacher.full_name}</div>
                      <div class="text-xs text-gray-500">{new Date(teacher.created_at).toLocaleDateString('tr-TR')}</div>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-700">
                      <div>{teacher.email || '—'}</div>
                      <div>{teacher.phone || '—'}</div>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-700">
                      <div>{teacher.university || '—'}</div>
                      <div class="text-xs text-gray-500">{teacher.department || ''}</div>
                    </td>
                    <td class="px-4 py-3 text-center text-gray-900 font-semibold">
                      €{teacher.hourly_rate || '-'}
                    </td>
                    <td class="px-4 py-3 text-center">
                      <span class={`px-3 py-1 rounded-full text-xs font-semibold ${teacher.approval_status === 'approved' ? 'bg-green-100 text-green-700' : teacher.approval_status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700'}`}>
                        {teacher.approval_status}
                      </span>
                    </td>
                    <td class="px-4 py-3">
                      <div class="flex flex-wrap gap-2 justify-center">
                        <button
                          class="px-3 py-1 text-xs rounded-full bg-blue-600 text-white hover:bg-blue-700"
                          on:click={() => openTeacherEdit(teacher.id)}
                        >
                          Düzenle
                        </button>
                        <button
                          class="px-3 py-1 text-xs rounded-full bg-green-600 text-white hover:bg-green-700 disabled:opacity-50"
                          on:click={() => updateTeacherStatus(teacher.id, 'approved')}
                          disabled={teacher.approval_status === 'approved'}
                        >
                          Onayla
                        </button>
                        <button
                          class="px-3 py-1 text-xs rounded-full bg-red-600 text-white hover:bg-red-700 disabled:opacity-50"
                          on:click={() => updateTeacherStatus(teacher.id, 'rejected')}
                          disabled={teacher.approval_status === 'rejected'}
                        >
                          Reddet
                        </button>
                        <button
                          class="px-3 py-1 text-xs rounded-full bg-gray-100 text-gray-800 hover:bg-gray-200"
                          on:click={() => toggleUserActive(teacher.id, teacher.is_active ? 0 : 1)}
                        >
                          {teacher.is_active ? 'Hesabı Kapat' : 'Hesabı Aç'}
                        </button>
                      </div>
                    </td>
                  </tr>
                {:else}
                  <tr>
                    <td colspan="6" class="px-4 py-6 text-center text-gray-500">Listelenecek öğretmen bulunamadı.</td>
                  </tr>
                {/each}
              </tbody>
            </table>
          </div>
        {/if}
      </section>
    {:else if activeTab === 'messages'}
      <section class="space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="text-2xl font-semibold text-gray-900">Mesaj Denetimi</h2>
          <button class="text-sm text-blue-600 hover:underline" on:click={loadRecentMessages}>Yenile</button>
        </div>

        <div class="grid lg:grid-cols-2 gap-6">
          <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 text-sm font-semibold text-gray-600">
              Son Mesajlar
            </div>
            {#if messagesLoading}
              <div class="flex justify-center py-8">
                <div class="w-6 h-6 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
              </div>
            {:else}
              <ul class="divide-y divide-gray-100 max-h-[520px] overflow-y-auto">
                {#each recentMessages as msg}
                  <li>
                    <button
                      type="button"
                      class="w-full text-left p-4 flex flex-col gap-1 hover:bg-gray-50"
                      on:click={() => viewConversation(msg.conversation_id)}
                    >
                      <div class="flex items-center justify-between">
                        <div class="font-semibold text-gray-900">{msg.sender_name}</div>
                        <div class="text-xs text-gray-500">{new Date(msg.created_at).toLocaleString('tr-TR')}</div>
                      </div>
                      <div class="text-xs text-gray-500">{msg.sender_role === 'student' ? 'Öğretmen' : 'Veli'} · Konuşma #{msg.conversation_id}</div>
                      <div class="text-sm text-gray-700 line-clamp-2">{msg.message_text}</div>
                    </button>
                  </li>
                {:else}
                  <li class="p-4 text-center text-gray-500">Mesaj bulunamadı.</li>
                {/each}
              </ul>
            {/if}
          </div>

          <div class="bg-white rounded-2xl border border-gray-100 p-4 min-h-[560px] space-y-3">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-lg font-semibold text-gray-900">Konuşma Detayı</h3>
                {#if selectedConversation}
                  <p class="text-sm text-gray-500">
                    Öğretmen: {selectedConversation.conversation.teacher_name || selectedConversation.conversation.teacher_id} ·
                    Veli: {selectedConversation.conversation.parent_name || selectedConversation.conversation.parent_id}
                  </p>
                {/if}
              </div>
              {#if conversationLoading}
                <div class="w-5 h-5 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
              {/if}
            </div>
            {#if selectedConversation}
              <div class="border border-gray-100 rounded-xl h-[460px] overflow-y-auto p-3 space-y-3 bg-gray-50">
                {#each selectedConversation.messages as m}
                  <div class={`p-3 rounded-xl ${m.sender_role === 'student' ? 'bg-blue-100 text-blue-900' : 'bg-white'}`}>
                    <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                      <span class="font-semibold text-gray-800">{m.sender_name}</span>
                      <span>{new Date(m.created_at).toLocaleString('tr-TR')}</span>
                    </div>
                    <p class="text-sm text-gray-800 whitespace-pre-line">{m.message_text}</p>
                    <div class="text-xs text-gray-500 mt-2">
                      <button
                        class="text-red-600 hover:underline"
                        on:click={() => toggleUserActive(m.sender_id, 0)}
                      >
                        Kullanıcıyı Engelle
                      </button>
                    </div>
                  </div>
                {/each}
              </div>
            {:else}
              <div class="h-[460px] flex items-center justify-center text-gray-400">
                İncelenecek bir konuşma seçin.
              </div>
            {/if}
          </div>
        </div>
      </section>
    {:else if activeTab === 'support'}
      <section class="space-y-4">
        <div class="flex items-center justify-between flex-wrap gap-4">
          <h2 class="text-2xl font-semibold text-gray-900">Destek Kutusu</h2>
          <div class="flex items-center gap-2 text-sm">
            <label for="support-filter">Durum:</label>
            <select
              id="support-filter"
              class="border rounded-lg px-3 py-1"
              bind:value={activeSupportFilter}
              on:change={() => loadSupportMessages(activeSupportFilter || null)}
            >
              <option value="">Tümü</option>
              <option value="new">Yeni</option>
              <option value="in_progress">İnceleniyor</option>
              <option value="resolved">Çözüldü</option>
            </select>
          </div>
        </div>
        {#if supportLoading}
          <div class="flex justify-center py-12">
            <div class="w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
          </div>
        {:else}
          <div class="space-y-4">
            {#each supportMessages as msg}
              <div class="bg-white border border-gray-100 rounded-2xl p-5 space-y-3">
                <div class="flex items-center justify-between flex-wrap gap-3">
                  <div>
                    <div class="font-semibold text-gray-900">{msg.name}</div>
                    <div class="text-sm text-gray-500">{msg.email}</div>
                  </div>
                  <span class={`px-3 py-1 rounded-full text-xs font-semibold ${msg.status === 'resolved' ? 'bg-green-100 text-green-700' : msg.status === 'in_progress' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'}`}>
                    {msg.status}
                  </span>
                </div>
                <div class="text-sm text-gray-600">
                  <strong>Konu:</strong> {msg.subject}
                </div>
                <div class="text-gray-800 whitespace-pre-line">
                  {msg.message}
                </div>
                <div class="text-xs text-gray-500">
                  {new Date(msg.created_at).toLocaleString('tr-TR')}
                </div>
                <textarea
                  class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm"
                  placeholder="Not ekleyin"
                  bind:value={msg.admin_notes}
                ></textarea>
                <div class="flex flex-wrap gap-2">
                  <button class="px-3 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800" on:click={() => updateSupport(msg, 'in_progress')}>İnceleniyor</button>
                  <button class="px-3 py-1 text-xs rounded-full bg-green-100 text-green-800" on:click={() => updateSupport(msg, 'resolved')}>Çözüldü</button>
                </div>
              </div>
            {:else}
              <div class="bg-white border border-dashed border-gray-200 rounded-2xl p-8 text-center text-gray-500">
                Gösterilecek mesaj yok.
              </div>
            {/each}
          </div>
        {/if}
      </section>
    {:else if activeTab === 'reviews'}
      <section class="space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="text-2xl font-semibold text-gray-900">Yorum Moderasyon</h2>
          <button class="text-sm text-blue-600 hover:underline" on:click={loadPendingReviews}>Yenile</button>
        </div>
        {#if reviewsLoading}
          <div class="flex justify-center py-10">
            <div class="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
          </div>
        {:else}
          <div class="grid gap-3">
            {#each pendingReviews as rev}
              <div class="bg-white border border-gray-100 rounded-2xl p-4 space-y-2">
                <div class="flex justify-between items-center">
                  <div class="font-semibold text-gray-900">{rev.teacher_name}</div>
                  <div class="text-sm text-gray-500">{rev.reviewer_name || 'Anonim'} • {rev.rating}/5</div>
                </div>
                <div class="text-sm text-gray-700 whitespace-pre-line">{rev.comment}</div>
                <div class="text-xs text-gray-500">{new Date(rev.created_at).toLocaleString('tr-TR')}</div>
                <div class="flex gap-2">
                  <button class="px-3 py-1 text-xs rounded-full bg-green-100 text-green-800" on:click={() => moderateReview(rev.id, 'approved')}>Onayla</button>
                  <button class="px-3 py-1 text-xs rounded-full bg-red-100 text-red-800" on:click={() => moderateReview(rev.id, 'rejected')}>Reddet</button>
                </div>
              </div>
            {:else}
              <div class="bg-white border border-dashed border-gray-200 rounded-2xl p-8 text-center text-gray-500">
                Bekleyen yorum yok.
              </div>
            {/each}
          </div>
        {/if}
      </section>
    {:else if activeTab === 'announcements'}
      <section class="grid lg:grid-cols-2 gap-6">
        <div class="bg-white border border-gray-100 rounded-2xl p-5 space-y-4">
          <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-900">Duyurular</h2>
            <button class="text-sm text-blue-600 hover:underline" on:click={loadAnnouncements}>Yenile</button>
          </div>
          {#if annLoading}
            <div class="flex justify-center py-10">
              <div class="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
            </div>
          {:else}
            <ul class="divide-y divide-gray-100 max-h-[520px] overflow-y-auto">
              {#each announcements as ann}
                <li class="py-3 flex flex-col gap-1">
                  <div class="flex items-center justify-between">
                    <div>
                      <div class="font-semibold text-gray-900">{ann.title}</div>
                      <div class="text-xs text-gray-500">{ann.slug}</div>
                    </div>
                    <div class="flex gap-2">
                      <button class="text-sm text-blue-600 hover:underline" on:click={() => editAnnouncement(ann)}>Düzenle</button>
                      <button class="text-sm text-red-600 hover:underline" on:click={() => deleteAnnouncement(ann.id)}>Sil</button>
                    </div>
                  </div>
                  <div class="text-xs text-gray-500 flex items-center gap-2">
                    <span>{ann.is_published ? 'Yayında' : 'Taslak'}</span>
                    {#if ann.award_name}<span>• {ann.award_name}</span>{/if}
                  </div>
                </li>
              {:else}
                <li class="py-6 text-center text-gray-500">Henüz duyuru yok.</li>
              {/each}
            </ul>
          {/if}
        </div>

        <div class="bg-white border border-gray-100 rounded-2xl p-5 space-y-3">
          <h2 class="text-xl font-semibold text-gray-900">{announcementForm.id ? 'Duyuru Düzenle' : 'Yeni Duyuru'}</h2>
          <div class="grid gap-3">
            <input class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500" placeholder="Slug" bind:value={announcementForm.slug} />
            <input class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500" placeholder="Başlık" bind:value={announcementForm.title} />
            <input class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500" placeholder="Ödül adı (opsiyonel)" bind:value={announcementForm.award_name} />
            <input type="month" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500" bind:value={announcementForm.award_month} />
            <MarkdownEditor bind:value={announcementForm.body} label="Duyuru İçeriği" />
            <div class="flex items-center justify-between">
              <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" bind:checked={announcementForm.is_published} />
                Yayında
              </label>
              <button class="text-sm text-blue-600 hover:underline" on:click={addWinner}>Ödül kazananı ekle</button>
            </div>
            {#if announcementForm.winners?.length}
              <div class="space-y-2">
                {#each announcementForm.winners as win, i}
                  <div class="border border-gray-200 rounded-lg p-3 grid gap-2">
                    <div class="flex gap-2">
                      <input class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Kullanıcı ID" bind:value={win.user_id} on:input={(e) => announcementForm.winners[i].user_id = e.target.value} />
                      <input class="w-20 border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Sıra" bind:value={win.rank} on:input={(e) => announcementForm.winners[i].rank = e.target.value} />
                      <button class="text-xs text-red-600" on:click={() => removeWinner(i)}>Sil</button>
                    </div>
                    <textarea class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Gerekçe" bind:value={win.rationale}></textarea>
                  </div>
                {/each}
              </div>
            {/if}
          </div>
          <div class="flex gap-3">
            <button class="bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-700 transition" on:click={saveAnnouncement}>Kaydet</button>
            {#if announcementForm.id}
              <button class="bg-gray-100 text-gray-800 px-4 py-2 rounded-lg font-semibold hover:bg-gray-200 transition" on:click={resetAnnouncement}>Yeni</button>
            {/if}
          </div>
        </div>
      </section>
    {:else if activeTab === 'blog'}
      <section class="grid lg:grid-cols-2 gap-6">
        <div class="bg-white border border-gray-100 rounded-2xl p-5 space-y-4">
          <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-900">Blog Yazıları</h2>
            <button class="text-sm text-blue-600 hover:underline" on:click={loadBlogPosts}>Yenile</button>
          </div>
          {#if blogLoading}
            <div class="flex justify-center py-10">
              <div class="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
            </div>
          {:else}
            <ul class="divide-y divide-gray-100 max-h-[520px] overflow-y-auto">
              {#each blogPosts as post}
                <li class="py-3 flex flex-col gap-1">
                  <div class="flex items-center justify-between">
                    <div>
                      <div class="font-semibold text-gray-900">{post.title}</div>
                      <div class="text-xs text-gray-500">{post.slug}</div>
                    </div>
                    <div class="flex gap-2">
                      <button class="text-sm text-blue-600 hover:underline" on:click={() => editPost(post)}>Düzenle</button>
                      <button class="text-sm text-red-600 hover:underline" on:click={() => deleteBlogPost(post.id)}>Sil</button>
                    </div>
                  </div>
                  <div class="text-xs text-gray-500 flex items-center gap-2">
                    <span>{post.is_published ? 'Yayında' : 'Taslak'}</span>
                    <span>•</span>
                    <span>{new Date(post.created_at).toLocaleDateString('tr-TR')}</span>
                  </div>
                </li>
              {:else}
                <li class="py-6 text-center text-gray-500">Henüz yazı yok.</li>
              {/each}
            </ul>
          {/if}
        </div>

        <div class="bg-white border border-gray-100 rounded-2xl p-5 space-y-3">
          <h2 class="text-xl font-semibold text-gray-900">{blogForm.id ? 'Yazıyı Düzenle' : 'Yeni Yazı'}</h2>
          <div class="grid gap-3">
            <input class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500" placeholder="Slug (ör: almanyada-turkce-egitimi)" bind:value={blogForm.slug} />
            <input class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500" placeholder="Başlık" bind:value={blogForm.title} />
            <input class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500" placeholder="Yazar" bind:value={blogForm.author} />
            <input class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500" placeholder="Kapak görseli URL" bind:value={blogForm.image} />
            <textarea class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 min-h-[80px]" placeholder="Kısa özet" bind:value={blogForm.excerpt}></textarea>
            <MarkdownEditor bind:value={blogForm.content_markdown} />
            <label class="flex items-center gap-2 text-sm text-gray-700">
              <input type="checkbox" bind:checked={blogForm.is_published} />
              Yayında
            </label>
          </div>
          <div class="flex gap-3">
            <button class="bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-700 transition" on:click={saveBlogPost}>Kaydet</button>
            {#if blogForm.id}
              <button class="bg-gray-100 text-gray-800 px-4 py-2 rounded-lg font-semibold hover:bg-gray-200 transition" on:click={resetBlogForm}>Yeni Yazı</button>
            {/if}
          </div>
        </div>
      </section>
    {:else if activeTab === 'rewards'}
      <section class="space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="text-2xl font-semibold text-gray-900">Ödül & Saat Takibi</h2>
          <button class="text-sm text-blue-600 hover:underline" on:click={loadRewards}>Yenile</button>
        </div>
        {#if rewardLoading}
          <div class="flex justify-center py-10">
            <div class="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
          </div>
        {:else}
          <div class="grid md:grid-cols-4 gap-4">
            <div class="bg-white border border-gray-100 rounded-2xl p-4">
              <div class="text-xs uppercase text-gray-500 tracking-wide">Veli Toplam Saat</div>
              <div class="text-2xl font-bold text-gray-900 mt-2">{rewardOverview.parent_hours?.toFixed(1)}</div>
            </div>
            <div class="bg-white border border-gray-100 rounded-2xl p-4">
              <div class="text-xs uppercase text-gray-500 tracking-wide">Öğretmen Toplam Saat</div>
              <div class="text-2xl font-bold text-gray-900 mt-2">{rewardOverview.teacher_hours?.toFixed(1)}</div>
            </div>
            <div class="bg-white border border-gray-100 rounded-2xl p-4">
              <div class="text-xs uppercase text-gray-500 tracking-wide">Aktif Veliler</div>
              <div class="text-2xl font-bold text-gray-900 mt-2">{rewardOverview.parent_count}</div>
            </div>
            <div class="bg-white border border-gray-100 rounded-2xl p-4">
              <div class="text-xs uppercase text-gray-500 tracking-wide">Aktif Öğretmenler</div>
              <div class="text-2xl font-bold text-gray-900 mt-2">{rewardOverview.teacher_count}</div>
            </div>
          </div>

          <div class="grid lg:grid-cols-2 gap-6">
            <div class="bg-white border border-gray-100 rounded-2xl p-5 space-y-3">
              <h3 class="font-semibold text-gray-900">Manuel Saat Ekle</h3>
              <div class="grid gap-3">
                <input class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500" placeholder="Kullanıcı ID" bind:value={rewardForm.user_id} />
                <input class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500" placeholder="Saat" type="number" step="0.5" bind:value={rewardForm.hours} />
                <textarea class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 min-h-[80px]" placeholder="Not (opsiyonel)" bind:value={rewardForm.notes}></textarea>
              </div>
              <button class="bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-700 transition" on:click={addManualHours}>Kaydet</button>
            </div>

            <div class="bg-white border border-gray-100 rounded-2xl p-5">
              <h3 class="font-semibold text-gray-900 mb-3">Son Saat Kayıtları</h3>
              <div class="max-h-[360px] overflow-y-auto divide-y divide-gray-100">
                {#each rewardHistory as item}
                  <div class="py-3 text-sm">
                    <div class="flex items-center justify-between">
                      <div class="font-semibold text-gray-900">{item.full_name}</div>
                      <div class="text-xs text-gray-500">{new Date(item.completed_at).toLocaleDateString('tr-TR')}</div>
                    </div>
                    <div class="text-gray-600">Rol: {item.role === 'student' ? 'Öğretmen' : 'Veli'} · {item.hours_completed} saat</div>
                    {#if item.notes}
                      <div class="text-xs text-gray-500 mt-1">{item.notes}</div>
                    {/if}
                  </div>
                {:else}
                  <div class="py-3 text-center text-gray-500">Kayıt yok</div>
                {/each}
              </div>
            </div>
          </div>
        {/if}
      </section>
    {:else if activeTab === 'podcast'}
      <PodcastManagement />
    {/if}
  </div>
{/if}

<!-- Teacher Edit Modal -->
{#if showTeacherEditModal}
  <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" on:click={closeTeacherEdit} on:keydown={(e) => e.key === 'Escape' && closeTeacherEdit()}>
    <div class="bg-white rounded-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto" on:click|stopPropagation>
      <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between z-10">
        <h2 class="text-2xl font-bold text-gray-900">Öğretmen Düzenle</h2>
        <button class="text-gray-400 hover:text-gray-600 text-2xl" on:click={closeTeacherEdit}>×</button>
      </div>
      
      <div class="p-6">
        {#if teacherEditLoading}
          <div class="flex justify-center py-12">
            <div class="w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
          </div>
        {:else if teacherEditForm}
          <form on:submit|preventDefault={saveTeacherEdit} class="space-y-6">
            <!-- Kişisel Bilgiler -->
            <div class="space-y-4">
              <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Kişisel Bilgiler</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Ad Soyad</label>
                  <input type="text" bind:value={teacherEditForm.full_name} class="w-full border rounded-lg px-3 py-2" required />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                  <input type="email" bind:value={teacherEditForm.email} class="w-full border rounded-lg px-3 py-2" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Telefon</label>
                  <input type="tel" bind:value={teacherEditForm.phone} class="w-full border rounded-lg px-3 py-2" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Onay Durumu</label>
                  <select bind:value={teacherEditForm.approval_status} class="w-full border rounded-lg px-3 py-2">
                    <option value="pending">Beklemede</option>
                    <option value="approved">Onaylı</option>
                    <option value="rejected">Reddedildi</option>
                  </select>
                </div>
                <div>
                  <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" checked={teacherEditForm.is_active} on:change={(e) => teacherEditForm.is_active = e.currentTarget?.checked || false} class="rounded" />
                    <span class="text-sm font-medium text-gray-700">Hesap Aktif</span>
                  </label>
                </div>
                <div>
                  <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" checked={teacherEditForm.is_verified} on:change={(e) => teacherEditForm.is_verified = e.currentTarget?.checked || false} class="rounded" />
                    <span class="text-sm font-medium text-gray-700">Doğrulanmış</span>
                  </label>
                </div>
              </div>
            </div>

            <!-- Profil Bilgileri -->
            <div class="space-y-4">
              <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Profil Bilgileri</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Üniversite</label>
                  <input type="text" bind:value={teacherEditForm.university} class="w-full border rounded-lg px-3 py-2" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Bölüm</label>
                  <input type="text" bind:value={teacherEditForm.department} class="w-full border rounded-lg px-3 py-2" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Mezuniyet Yılı</label>
                  <input type="number" bind:value={teacherEditForm.graduation_year} min="1950" max="2030" class="w-full border rounded-lg px-3 py-2" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Deneyim (Yıl)</label>
                  <input type="number" bind:value={teacherEditForm.experience_years} min="0" max="50" class="w-full border rounded-lg px-3 py-2" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Saatlik Ücret (€)</label>
                  <input type="number" bind:value={teacherEditForm.hourly_rate} min="0" step="0.5" class="w-full border rounded-lg px-3 py-2" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Rating (0-5)</label>
                  <input type="number" bind:value={teacherEditForm.rating_avg} min="0" max="5" step="0.1" class="w-full border rounded-lg px-3 py-2" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Review Sayısı</label>
                  <input type="number" bind:value={teacherEditForm.review_count} min="0" class="w-full border rounded-lg px-3 py-2" />
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Bio</label>
                <textarea bind:value={teacherEditForm.bio} rows="4" class="w-full border rounded-lg px-3 py-2"></textarea>
              </div>
            </div>

            <!-- Lokasyon -->
            <div class="space-y-4">
              <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Lokasyon</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Şehir</label>
                  <input type="text" bind:value={teacherEditForm.city} class="w-full border rounded-lg px-3 py-2" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Posta Kodu</label>
                  <input type="text" bind:value={teacherEditForm.zip_code} class="w-full border rounded-lg px-3 py-2" />
                </div>
              </div>
            </div>

            <!-- Dersler -->
            <div class="space-y-4">
              <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Dersler</h3>
              <div class="grid grid-cols-2 md:grid-cols-3 gap-3 max-h-64 overflow-y-auto border rounded-lg p-4">
                {#each allSubjects as subject}
                  {@const isSelected = teacherEditForm.subjects.some(s => s.id === subject.id)}
                  <label class="flex items-start gap-2 p-2 rounded hover:bg-gray-50 cursor-pointer">
                    <input 
                      type="checkbox" 
                      checked={isSelected}
                      on:change={() => toggleSubject(subject.id)}
                      class="mt-1 rounded"
                    />
                    <div class="flex-1">
                      <div class="text-sm font-medium text-gray-900">{subject.icon} {subject.name}</div>
                      {#if isSelected}
                        {@const selectedSubject = teacherEditForm.subjects.find(s => s.id === subject.id)}
                        <select 
                          value={selectedSubject?.proficiency_level || 'intermediate'}
                          on:change={(e) => updateSubjectLevel(subject.id, e)}
                          class="mt-1 text-xs border rounded px-2 py-1 w-full"
                        >
                          <option value="beginner">Başlangıç</option>
                          <option value="intermediate">Orta</option>
                          <option value="advanced">İleri</option>
                          <option value="expert">Uzman</option>
                        </select>
                      {/if}
                    </div>
                  </label>
                {/each}
              </div>
            </div>

            <!-- Butonlar -->
            <div class="flex justify-end gap-3 pt-4 border-t">
              <button 
                type="button"
                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                on:click={closeTeacherEdit}
                disabled={teacherEditSaving}
              >
                İptal
              </button>
              <button 
                type="submit"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                disabled={teacherEditSaving}
              >
                {teacherEditSaving ? 'Kaydediliyor...' : 'Kaydet'}
              </button>
            </div>
          </form>
        {/if}
      </div>
    </div>
  </div>
{/if}
