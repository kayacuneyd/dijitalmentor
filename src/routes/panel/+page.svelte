<script>
  import { authStore } from '$lib/stores/auth.js';
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import RewardsPanel from '$lib/components/RewardsPanel.svelte';
  import { api } from '$lib/utils/api.js';
  import { toast } from '$lib/stores/toast.js';
  
  let lessonReports = [];
  let lessonAgreements = [];
  let loadingReports = false;
  let showReportForm = false;
  let reportForm = {
    agreement_id: null,
    lesson_date: '',
    attendance: 'present',
    topics_covered: '',
    homework_assigned: '',
    teacher_notes: '',
    student_progress: '',
    next_lesson_date: ''
  };
  let submittingReport = false;
  
  $: user = $authStore.user;
  $: if ($authStore.isAuthenticated && user?.role === 'admin') {
    goto('/panel/admin');
  }
  
  onMount(async () => {
    if (!$authStore.isAuthenticated) {
      goto('/giris');
      return;
    }
    
    if (user?.role === 'student') {
      await loadLessonAgreements();
    }
    await loadLessonReports();
  });
  
  async function loadLessonAgreements() {
    try {
      const res = await api.get('/lessons/agreements.php', { status: 'confirmed' });
      lessonAgreements = res.data || [];
    } catch (e) {
      console.error('Error loading agreements:', e);
    }
  }
  
  async function loadLessonReports() {
    loadingReports = true;
    try {
      const res = await api.get('/lessons/reports.php');
      lessonReports = res.data || [];
    } catch (e) {
      console.error('Error loading reports:', e);
    } finally {
      loadingReports = false;
    }
  }
  
  function openReportForm(agreementId = null) {
    if (agreementId) {
      reportForm.agreement_id = agreementId;
    } else if (lessonAgreements.length > 0) {
      reportForm.agreement_id = lessonAgreements[0].id;
    }
    showReportForm = true;
  }
  
  async function submitReport() {
    if (!reportForm.agreement_id || !reportForm.lesson_date) {
      toast.error('Lütfen anlaşma ve ders tarihi seçin');
      return;
    }
    
    submittingReport = true;
    try {
      await api.post('/lessons/create_report.php', reportForm);
      toast.success('Ders raporu başarıyla oluşturuldu');
      showReportForm = false;
      reportForm = {
        agreement_id: null,
        lesson_date: '',
        attendance: 'present',
        topics_covered: '',
        homework_assigned: '',
        teacher_notes: '',
        student_progress: '',
        next_lesson_date: ''
      };
      await loadLessonReports();
    } catch (e) {
      toast.error(e.message || 'Rapor oluşturulamadı');
    } finally {
      submittingReport = false;
    }
  }
</script>

<svelte:head>
  <title>Panelim - DijitalMentor</title>
</svelte:head>

{#if $authStore.isAuthenticated}
<div class="min-h-screen bg-gray-50/50 pb-12">
  <!-- Welcome Header -->
  <div class="bg-white border-b border-gray-100">
    <div class="container mx-auto px-4 py-8">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Hoşgeldin, {user?.full_name} 👋</h1>
          <p class="text-gray-500 mt-1">Bugün ne yapmak istersin?</p>
        </div>
        <div class="flex items-center gap-3">
          <span class="px-4 py-2 bg-blue-50 text-blue-700 rounded-full text-sm font-semibold border border-blue-100">
            {user?.role === 'student' ? 'Öğretmen Hesabı' : 'Veli / Öğrenci Hesabı'}
          </span>
        </div>
      </div>
    </div>
  </div>

  <div class="container mx-auto px-4 py-8">
    
    <!-- Approval Status Alert for Teachers -->
    {#if user?.role === 'student' && user?.approval_status === 'pending'}
      <div class="mb-6 bg-orange-50 border-l-4 border-orange-500 p-6 rounded-lg">
        <div class="flex items-start gap-4">
          <div class="text-3xl">⏳</div>
          <div class="flex-1">
            <h3 class="font-bold text-orange-900 text-lg mb-2">Profiliniz İnceleniyor</h3>
            <p class="text-orange-800 leading-relaxed">
              Öğretmen kaydınız yöneticilerimiz tarafından incelenmektedir. Onaylandıktan sonra profiliniz öğrenci ve veliler tarafından görülebilecek ve haritada listelenecektir.
            </p>
            <p class="text-orange-700 text-sm mt-3">
              💡 Bu süreçte <a href="/panel/ayarlar" class="underline font-semibold">profil bilgilerinizi</a> tamamlayabilirsiniz.
            </p>
          </div>
        </div>
      </div>
    {/if}
    
    <div class="grid md:grid-cols-3 gap-6">
      
      <!-- Quick Actions -->
      <div class="md:col-span-2 space-y-6">
        <h2 class="text-lg font-bold text-gray-900">Hızlı İşlemler</h2>
        
        <div class="grid sm:grid-cols-2 gap-4">
          <!-- Profile Card -->
          <a href="/panel/ayarlar" class="group bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md hover:border-blue-200 transition flex items-start gap-4">
            <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center text-2xl group-hover:scale-110 transition">
              👤
            </div>
            <div>
              <h3 class="font-bold text-gray-900 mb-1">Profilim</h3>
              <p class="text-sm text-gray-500">Kişisel bilgilerinizi ve tercihlerinizi düzenleyin.</p>
            </div>
          </a>

          <!-- Messages Card -->
          <a href="/panel/mesajlar" class="group bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md hover:border-green-200 transition flex items-start gap-4">
            <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center text-2xl group-hover:scale-110 transition">
              💬
            </div>
            <div>
              <h3 class="font-bold text-gray-900 mb-1">Mesajlar</h3>
              <p class="text-sm text-gray-500">Öğretmenler ve velilerle olan görüşmeleriniz.</p>
            </div>
          </a>

          {#if user?.role === 'parent'}
            <!-- Requests Card -->
            <a href="/panel/taleplerim" class="group bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md hover:border-purple-200 transition flex items-start gap-4">
              <div class="w-12 h-12 bg-purple-50 rounded-xl flex items-center justify-center text-2xl group-hover:scale-110 transition">
                📝
              </div>
              <div>
                <h3 class="font-bold text-gray-900 mb-1">Ders Taleplerim</h3>
                <p class="text-sm text-gray-500">Oluşturduğunuz özel ders ilanlarını yönetin.</p>
              </div>
            </a>
            
            <!-- New Request Card -->
            <a href="/panel/taleplerim/yeni" class="group bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md hover:border-orange-200 transition flex items-start gap-4">
              <div class="w-12 h-12 bg-orange-50 rounded-xl flex items-center justify-center text-2xl group-hover:scale-110 transition">
                ✨
              </div>
              <div>
                <h3 class="font-bold text-gray-900 mb-1">Yeni Talep</h3>
                <p class="text-sm text-gray-500">Hızlıca yeni bir özel ders ilanı oluşturun.</p>
              </div>
            </a>
          {:else if user?.role === 'student'}
            <!-- Lesson Reports Card -->
            <button
              on:click={openReportForm}
              class="group bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md hover:border-green-200 transition flex items-start gap-4 w-full text-left"
            >
              <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center text-2xl group-hover:scale-110 transition">
                📊
              </div>
              <div>
                <h3 class="font-bold text-gray-900 mb-1">Ders Raporu Oluştur</h3>
                <p class="text-sm text-gray-500">Ders sonrası rapor doldurun</p>
              </div>
            </button>
          {/if}
        </div>
      </div>

      <!-- Sidebar / Stats -->
      <div class="space-y-6">
        <h2 class="text-lg font-bold text-gray-900">Özet</h2>
        
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
          <div class="flex items-center gap-4 mb-6">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center text-2xl font-bold text-gray-600">
              {user?.full_name?.charAt(0) || '?'}
            </div>
            <div>
              <div class="font-bold text-gray-900">{user?.full_name}</div>
              <div class="text-sm text-gray-500">{user?.city || 'Konum belirtilmedi'}</div>
            </div>
          </div>
          
          {#if user?.role === 'student'}
            <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-50">
              <div class="text-center">
                <div class="text-2xl font-bold text-blue-600">0</div>
                <div class="text-xs text-gray-500 uppercase tracking-wider mt-1">Görüntülenme</div>
              </div>
              <div class="text-center">
                <div class="text-2xl font-bold text-green-600">0</div>
                <div class="text-xs text-gray-500 uppercase tracking-wider mt-1">Yorum</div>
              </div>
            </div>
          {:else}
             <div class="pt-4 border-t border-gray-50">
               <div class="flex justify-between items-center mb-2">
                 <span class="text-sm text-gray-600">Üyelik Durumu</span>
                 <span class="text-sm font-bold text-green-600">Aktif</span>
               </div>
               <div class="flex justify-between items-center">
                 <span class="text-sm text-gray-600">Son Giriş</span>
                 <span class="text-sm text-gray-900">{new Date().toLocaleDateString('tr-TR')}</span>
               </div>
             </div>
          {/if}
        </div>
        
        {#if user && (user.role === 'student' || user.role === 'parent')}
          <RewardsPanel />
        {/if}
        
        <!-- Lesson Reports Section -->
        {#if user?.role === 'student' || user?.role === 'parent'}
          <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-4">
              <h3 class="font-bold text-gray-900">Ders Raporları</h3>
              {#if user?.role === 'student'}
                <button
                  on:click={openReportForm}
                  class="text-sm text-blue-600 hover:underline font-semibold"
                >
                  + Yeni Rapor
                </button>
              {/if}
            </div>
            
            {#if loadingReports}
              <div class="text-center py-4 text-gray-500">Yükleniyor...</div>
            {:else if lessonReports.length === 0}
              <div class="text-center py-4 text-gray-500 text-sm">Henüz rapor yok</div>
            {:else}
              <div class="space-y-3 max-h-64 overflow-y-auto">
                {#each lessonReports.slice(0, 5) as report}
                  <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                    <div class="flex items-center justify-between mb-2">
                      <div class="flex items-center gap-2">
                        <span class="text-lg">{report.subject_icon}</span>
                        <span class="font-semibold text-gray-900 text-sm">{report.subject_name}</span>
                      </div>
                      <span class="text-xs text-gray-500">
                        {new Date(report.lesson_date).toLocaleDateString('tr-TR')}
                      </span>
                    </div>
                    <div class="text-xs text-gray-600">
                      <p class="mb-1">
                        <span class="font-semibold">Katılım:</span> {
                          report.attendance === 'present' ? '✓ Geldi' :
                          report.attendance === 'absent' ? '✗ Gelmedi' :
                          '⏰ Geç Geldi'
                        }
                      </p>
                      {#if report.topics_covered}
                        <p class="truncate"><span class="font-semibold">Konular:</span> {report.topics_covered}</p>
                      {/if}
                    </div>
                    <a
                      href="/panel/raporlar/{report.id}"
                      class="text-xs text-blue-600 hover:underline mt-2 inline-block"
                    >
                      Detayları gör →
                    </a>
                  </div>
                {/each}
              </div>
              {#if lessonReports.length > 5}
                <a
                  href="/panel/raporlar"
                  class="block text-center text-sm text-blue-600 hover:underline mt-3"
                >
                  Tümünü gör ({lessonReports.length})
                </a>
              {/if}
            {/if}
          </div>
        {/if}
        
        <!-- Support Card -->
        <div class="bg-gradient-to-br from-blue-600 to-blue-700 p-6 rounded-2xl shadow-lg text-white">
          <h3 class="font-bold text-lg mb-2">Yardıma mı ihtiyacınız var?</h3>
          <p class="text-blue-100 text-sm mb-4">Destek ekibimiz haftanın 7 günü sorularınızı yanıtlamak için burada.</p>
          <button class="w-full bg-white text-blue-600 py-2 rounded-lg font-bold text-sm hover:bg-blue-50 transition">
            Destek ile İletişime Geç
          </button>
        </div>
      </div>
      
    </div>
  </div>
</div>

<!-- Report Form Modal (Teachers Only) -->
{#if showReportForm && user?.role === 'student'}
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 backdrop-blur-sm" role="dialog" aria-modal="true">
    <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto p-6 shadow-2xl" on:click|stopPropagation>
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-xl font-bold text-gray-900">Ders Sonu Raporu</h3>
        <button
          on:click={() => showReportForm = false}
          class="text-gray-400 hover:text-gray-600 text-2xl"
        >
          ×
        </button>
      </div>
      
      <form on:submit|preventDefault={submitReport} class="space-y-4">
        <!-- Agreement Selection -->
        {#if lessonAgreements.length > 0}
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Hangi anlaşma için rapor? *
            </label>
            <select
              bind:value={reportForm.agreement_id}
              class="w-full border rounded-lg px-3 py-2"
              required
            >
              <option value="">Anlaşma seçin</option>
              {#each lessonAgreements as agreement}
                <option value={agreement.id}>
                  {agreement.subject_icon} {agreement.subject_name} - {new Date(agreement.lesson_date).toLocaleDateString('tr-TR')}
                </option>
              {/each}
            </select>
          </div>
        {/if}
        
        <!-- Lesson Date -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Ders Tarihi *
          </label>
          <input
            type="date"
            bind:value={reportForm.lesson_date}
            class="w-full border rounded-lg px-3 py-2"
            required
          />
        </div>
        
        <!-- Attendance -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Katılım Durumu *
          </label>
          <div class="grid grid-cols-3 gap-2">
            <button
              type="button"
              class={`border rounded-lg px-3 py-2 text-sm transition ${
                reportForm.attendance === 'present' 
                  ? 'border-green-500 text-green-700 bg-green-50' 
                  : 'border-gray-200 text-gray-700 hover:bg-gray-50'
              }`}
              on:click={() => reportForm.attendance = 'present'}
            >
              ✓ Geldi
            </button>
            <button
              type="button"
              class={`border rounded-lg px-3 py-2 text-sm transition ${
                reportForm.attendance === 'late' 
                  ? 'border-yellow-500 text-yellow-700 bg-yellow-50' 
                  : 'border-gray-200 text-gray-700 hover:bg-gray-50'
              }`}
              on:click={() => reportForm.attendance = 'late'}
            >
              ⏰ Geç Geldi
            </button>
            <button
              type="button"
              class={`border rounded-lg px-3 py-2 text-sm transition ${
                reportForm.attendance === 'absent' 
                  ? 'border-red-500 text-red-700 bg-red-50' 
                  : 'border-gray-200 text-gray-700 hover:bg-gray-50'
              }`}
              on:click={() => reportForm.attendance = 'absent'}
            >
              ✗ Gelmedi
            </button>
          </div>
        </div>
        
        <!-- Topics Covered -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            İşlenen Konular
          </label>
          <textarea
            bind:value={reportForm.topics_covered}
            rows="3"
            class="w-full border rounded-lg px-3 py-2"
            placeholder="Bu derste hangi konular işlendi?"
          ></textarea>
        </div>
        
        <!-- Homework Assigned -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Verilen Ödevler
          </label>
          <textarea
            bind:value={reportForm.homework_assigned}
            rows="2"
            class="w-full border rounded-lg px-3 py-2"
            placeholder="Öğrenciye verilen ödevler..."
          ></textarea>
        </div>
        
        <!-- Teacher Notes -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Öğretmen Notları
          </label>
          <textarea
            bind:value={reportForm.teacher_notes}
            rows="3"
            class="w-full border rounded-lg px-3 py-2"
            placeholder="Ders hakkında notlarınız..."
          ></textarea>
        </div>
        
        <!-- Student Progress -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Öğrenci Gelişimi
          </label>
          <textarea
            bind:value={reportForm.student_progress}
            rows="3"
            class="w-full border rounded-lg px-3 py-2"
            placeholder="Öğrencinin gelişimi hakkında gözlemleriniz..."
          ></textarea>
        </div>
        
        <!-- Next Lesson Date -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Sonraki Ders Tarihi
          </label>
          <input
            type="date"
            bind:value={reportForm.next_lesson_date}
            class="w-full border rounded-lg px-3 py-2"
          />
        </div>
        
        <!-- Buttons -->
        <div class="flex gap-3 pt-4">
          <button
            type="button"
            on:click={() => showReportForm = false}
            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
            disabled={submittingReport}
          >
            İptal
          </button>
          <button
            type="submit"
            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
            disabled={submittingReport}
          >
            {submittingReport ? 'Kaydediliyor...' : 'Raporu Kaydet'}
          </button>
        </div>
      </form>
    </div>
  </div>
{/if}
{:else}
  <div class="container mx-auto px-4 py-20 flex flex-col items-center gap-4 text-center">
    <div class="w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
    <p class="text-gray-600">Yönlendiriliyorsunuz...</p>
  </div>
{/if}
