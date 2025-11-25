<script>
  import { onMount } from 'svelte';
  import { page } from '$app/stores';
  import { authStore } from '$lib/stores/auth.js';
  import { api } from '$lib/utils/api.js';
  import { goto } from '$app/navigation';
  import { toast } from '$lib/stores/toast.js';

  let report = null;
  let loading = true;

  $: reportId = $page.params.id;
  $: user = $authStore.user;

  onMount(async () => {
    if (!$authStore.isAuthenticated) {
      goto('/giris');
      return;
    }
    await loadReport();
  });

  async function loadReport() {
    loading = true;
    try {
      const res = await api.get('/lessons/report_detail.php', { id: reportId });
      report = res.data;
    } catch (e) {
      toast.error('Rapor yüklenemedi');
      goto('/panel/raporlar');
    } finally {
      loading = false;
    }
  }
</script>

<svelte:head>
  <title>Ders Raporu - DijitalMentor</title>
</svelte:head>

<div class="container mx-auto px-4 py-8">
  <a href="/panel/raporlar" class="text-blue-600 hover:underline mb-4 inline-block">
    ← Raporlara Dön
  </a>

  {#if loading}
    <div class="flex justify-center py-12">
      <div class="w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
    </div>
  {:else if report}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 max-w-3xl mx-auto">
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Ders Raporu</h1>
        <div class="flex items-center gap-4 text-sm text-gray-600">
          <span>{report.subject_icon} {report.subject_name}</span>
          <span>•</span>
          <span>{new Date(report.lesson_date).toLocaleDateString('tr-TR', { 
            day: 'numeric', 
            month: 'long', 
            year: 'numeric' 
          })}</span>
        </div>
      </div>
      
      <div class="space-y-6">
        <!-- Attendance -->
        <div class="pb-4 border-b border-gray-200">
          <h3 class="font-semibold text-gray-900 mb-2">Katılım Durumu</h3>
          <div class="text-lg {
            report.attendance === 'present' ? 'text-green-600' :
            report.attendance === 'absent' ? 'text-red-600' :
            'text-yellow-600'
          }">
            {
              report.attendance === 'present' ? '✓ Öğrenci derse geldi' :
              report.attendance === 'absent' ? '✗ Öğrenci derse gelmedi' :
              '⏰ Öğrenci derse geç geldi'
            }
          </div>
        </div>
        
        <!-- Topics Covered -->
        {#if report.topics_covered}
          <div>
            <h3 class="font-semibold text-gray-900 mb-2">İşlenen Konular</h3>
            <p class="text-gray-700 whitespace-pre-wrap leading-relaxed">{report.topics_covered}</p>
          </div>
        {/if}
        
        <!-- Homework -->
        {#if report.homework_assigned}
          <div>
            <h3 class="font-semibold text-gray-900 mb-2">Verilen Ödevler</h3>
            <p class="text-gray-700 whitespace-pre-wrap leading-relaxed">{report.homework_assigned}</p>
          </div>
        {/if}
        
        <!-- Teacher Notes -->
        {#if report.teacher_notes}
          <div>
            <h3 class="font-semibold text-gray-900 mb-2">Öğretmen Notları</h3>
            <p class="text-gray-700 whitespace-pre-wrap leading-relaxed">{report.teacher_notes}</p>
          </div>
        {/if}
        
        <!-- Student Progress -->
        {#if report.student_progress}
          <div>
            <h3 class="font-semibold text-gray-900 mb-2">Öğrenci Gelişimi</h3>
            <p class="text-gray-700 whitespace-pre-wrap leading-relaxed">{report.student_progress}</p>
          </div>
        {/if}
        
        <!-- Next Lesson -->
        {#if report.next_lesson_date}
          <div class="pt-4 border-t border-gray-200">
            <h3 class="font-semibold text-gray-900 mb-2">Sonraki Ders</h3>
            <p class="text-gray-700">
              {new Date(report.next_lesson_date).toLocaleDateString('tr-TR', { 
                day: 'numeric', 
                month: 'long', 
                year: 'numeric' 
              })}
            </p>
          </div>
        {/if}
      </div>
    </div>
  {/if}
</div>

