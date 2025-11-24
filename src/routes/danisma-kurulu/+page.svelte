<svelte:head>
  <title>DijitalMentor Danışma Kurulu</title>
</svelte:head>

<section class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50">
  <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white py-16">
    <div class="container mx-auto px-4 text-center">
      <h1 class="text-4xl md:text-5xl font-bold mb-4">Danışma Kurulu</h1>
      <p class="text-lg md:text-xl text-blue-100 max-w-2xl mx-auto">
        Mentor onay süreçleri akademik, etik ve topluluk kriterleri ışığında bağımsız bir kurul tarafından yürütülür.
      </p>
    </div>
  </div>

  <div class="container mx-auto px-4 py-12 space-y-10 max-w-4xl">

    <div class="grid md:grid-cols-2 gap-6">
      <div class="bg-gray-50 border border-gray-100 rounded-2xl p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-gray-900 mb-3">Kurulun Görevleri</h2>
        <ul class="list-disc list-inside text-gray-700 space-y-2">
          <li>Kimlik ve üniversite belgesi doğrulaması</li>
          <li>Etik ve topluluk ilkelerine uyum kontrolü</li>
          <li>Şikayet ve ihbarların incelenmesi</li>
          <li>Ödül ve rozet önerilerinin onayı</li>
        </ul>
      </div>
      <div class="bg-gray-50 border border-gray-100 rounded-2xl p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-gray-900 mb-3">Neden Önemli?</h2>
        <p class="text-gray-700 leading-relaxed">
          Kurul, ticari kaygılardan bağımsız şekilde karar verir ve veliler için güven katmanı oluşturur.
          Onay almayan mentor listelenmez; gerekirse hesap askıya alınır veya topluluk uyarılır.
        </p>
      </div>
    </div>

    <div class="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm">
      <h2 class="text-xl font-semibold text-gray-900 mb-3">Kurul Üyeleri</h2>
      <p class="text-gray-700 mb-4">
        Üyeler; eğitimciler, üniversite temsilcileri ve topluluk gönüllülerinden oluşur. İsimler ve uzmanlık alanları, şeffaflık için
        duyurular sayfasında yayınlanır ve periyodik olarak güncellenir.
      </p>
      <p class="text-sm text-gray-500">
        Kurul üyelerinin listesi ve dönemsel kararlar yakında duyurulacaktır.
      </p>
    </div>

    <div class="bg-primary-50 border border-primary-100 rounded-2xl p-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <h3 class="text-xl font-semibold text-gray-900">Sorunuz veya ihbarınız mı var?</h3>
        <p class="text-gray-700">Kurulun değerlendirmesine iletilmesini istediğiniz durumları bize yazın.</p>
      </div>
      <a
        href="/iletisim"
        class="inline-block bg-primary-600 text-white font-semibold px-5 py-3 rounded-xl hover:bg-primary-700 transition"
      >
        Kurula ilet
      </a>
    </div>
  </div>
</section>
