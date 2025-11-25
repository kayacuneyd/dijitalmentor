<script>
  import { onMount } from 'svelte';
  import { authStore } from '$lib/stores/auth.js';
  import { api } from '$lib/utils/api.js';
  import { goto } from '$app/navigation';
  import { toast } from '$lib/stores/toast.js';

  let lessonReports = [];
  let loading = true;
  let selectedReport = null;

  $: user = $authStore.user;

  onMount(async () => {
    if (!$authStore.isAuthenticated) {
      goto('/giris');
      return;
    }
    await loadReports();
  });

  async function loadReports() {
    loading = true;
    try {
      const res = await api.get('/lessons/reports.php');
      lessonReports = res.data || [];
    } catch (e) {
      toast.error('Raporlar yüklenemedi');
    } finally {
      loading = false;
    }
  }

  async function viewReport(reportId) {
    try {
      const res = await api.get('/lessons/report_detail.php', { id: reportId });
      selectedReport = res.data;
    } catch (e) {
      toast.error('Rapor detayı yüklenemedi');
    }
  }

  function closeReportDetail() {
    selectedReport = null;
  }
</script>

<svelte:head>
  <title>Ders Raporları - DijitalMentor</title>
</svelte:head>

<div class="container mx-auto px-4 py-8">
  <div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 mb-2">Ders Raporları</h1>
    <p class="text-gray-600">
      {user?.role === 'student' 
        ? 'Ders sonrası doldurduğunuz raporlar' 
        : 'Çocuğunuzun ders raporları'}
    </p>
  </div>

  {#if loading}
    <div class="flex justify-center py-12">
      <div class="w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
    </div>
  {:else if lessonReports.length === 0}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
      <div class="text-6xl mb-4">📊</div>
      <h3 class="text-xl font-bold text-gray-900 mb-2">Henüz rapor yok</h3>
      <p class="text-gray-600">
        {user?.role === 'student' 
          ? 'İlk ders raporunuzu oluşturmak için panel ana sayfasından "Ders Raporu Oluştur" butonunu kullanın.'
          : 'Öğretmenler ders sonrası rapor doldurdukça burada görünecek.'}
      </p>
    </div>
  {:else}
    <div class="grid gap-4">
      {#each lessonReports as report}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition cursor-pointer" on:click={() => viewReport(report.id)}>
          <div class="flex items-start justify-between mb-4">
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center text-2xl">
                {report.subject_icon}
              </div>
              <div>
                <h3 class="font-bold text-gray-900">{report.subject_name}</h3>
                <p class="text-sm text-gray-500">
                  {user?.role === 'student' ? report.parent_name : report.teacher_name}
                </p>
              </div>
            </div>
            <div class="text-right">
              <div class="text-sm font-semibold text-gray-900">
                {new Date(report.lesson_date).toLocaleDateString('tr-TR', { 
                  day: 'numeric', 
                  month: 'long', 
                  year: 'numeric' 
                })}
              </div>
              <div class="text-xs text-gray-500 mt-1">
                {
                  report.attendance === 'present' ? '✓ Geldi' :
                  report.attendance === 'absent' ? '✗ Gelmedi' :
                  '⏰ Geç Geldi'
                }
              </div>
            </div>
          </div>
          
          {#if report.topics_covered}
            <p class="text-sm text-gray-700 mb-2 line-clamp-2">
              <span class="font-semibold">İşlenen Konular:</span> {report.topics_covered}
            </p>
          {/if}
          
          {#if report.teacher_notes}
            <p class="text-sm text-gray-600 line-clamp-2">
              {report.teacher_notes}
            </p>
          {/if}
          
          <button class="mt-4 text-sm text-blue-600 hover:underline font-semibold">
            Detayları gör →
          </button>
        </div>
      {/each}
    </div>
  {/if}
</div>

<!-- Report Detail Modal -->
{#if selectedReport}
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" on:click={closeReportDetail}>
    <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto p-6 shadow-2xl" on:click|stopPropagation>
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-xl font-bold text-gray-900">Ders Raporu Detayı</h3>
        <button
          on:click={closeReportDetail}
          class="text-gray-400 hover:text-gray-600 text-2xl"
        >
          ×
        </button>
      </div>
      
      <div class="space-y-6">
        <!-- Header Info -->
        <div class="grid grid-cols-2 gap-4 pb-4 border-b border-gray-200">
          <div>
            <div class="text-sm text-gray-500">Ders</div>
            <div class="font-semibold text-gray-900">
              {selectedReport.subject_icon} {selectedReport.subject_name}
            </div>
          </div>
          <div>
            <div class="text-sm text-gray-500">Tarih</div>
            <div class="font-semibold text-gray-900">
              {new Date(selectedReport.lesson_date).toLocaleDateString('tr-TR')}
            </div>
          </div>
          <div>
            <div class="text-sm text-gray-500">Katılım</div>
            <div class="font-semibold {
              selectedReport.attendance === 'present' ? 'text-green-600' :
              selectedReport.attendance === 'absent' ? 'text-red-600' :
              'text-yellow-600'
            }">
              {
                selectedReport.attendance === 'present' ? '✓ Geldi' :
                selectedReport.attendance === 'absent' ? '✗ Gelmedi' :
                '⏰ Geç Geldi'
              }
            </div>
          </div>
          {#if selectedReport.next_lesson_date}
            <div>
              <div class="text-sm text-gray-500">Sonraki Ders</div>
              <div class="font-semibold text-gray-900">
                {new Date(selectedReport.next_lesson_date).toLocaleDateString('tr-TR')}
              </div>
            </div>
          {/if}
        </div>
        
        <!-- Topics Covered -->
        {#if selectedReport.topics_covered}
          <div>
            <h4 class="font-semibold text-gray-900 mb-2">İşlenen Konular</h4>
            <p class="text-gray-700 whitespace-pre-wrap">{selectedReport.topics_covered}</p>
          </div>
        {/if}
        
        <!-- Homework -->
        {#if selectedReport.homework_assigned}
          <div>
            <h4 class="font-semibold text-gray-900 mb-2">Verilen Ödevler</h4>
            <p class="text-gray-700 whitespace-pre-wrap">{selectedReport.homework_assigned}</p>
          </div>
        {/if}
        
        <!-- Teacher Notes -->
        {#if selectedReport.teacher_notes}
          <div>
            <h4 class="font-semibold text-gray-900 mb-2">Öğretmen Notları</h4>
            <p class="text-gray-700 whitespace-pre-wrap">{selectedReport.teacher_notes}</p>
          </div>
        {/if}
        
        <!-- Student Progress -->
        {#if selectedReport.student_progress}
          <div>
            <h4 class="font-semibold text-gray-900 mb-2">Öğrenci Gelişimi</h4>
            <p class="text-gray-700 whitespace-pre-wrap">{selectedReport.student_progress}</p>
          </div>
        {/if}
      </div>
      
      <button
        on:click={closeReportDetail}
        class="w-full mt-6 px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition"
      >
        Kapat
      </button>
    </div>
  </div>
{/if}

