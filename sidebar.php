<?php
// DEBUG: currentPage değerini kontrol et
echo "<!-- DEBUG: currentPage = " . basename($_SERVER['PHP_SELF']) . " -->";

/**
 * Personel Takip Sistemi - Sidebar (Sol Menü)
 *
 * @version 1.7
 * @author Fatih
 * @license MIT
 */

require_once __DIR__ . '/config/session_manager.php';
SessionManager::requireAuth(); // Giriş yapılmamışsa login'e yönlendir

// Mevcut sayfanın adını al
$currentPage = basename($_SERVER['PHP_SELF']);

// ANASAYFA'DA İKEN AKTİF TC'Yİ TEMİZLE
if ($currentPage === 'dashboard_Anasayfa.php') {
    unset($_SESSION['aktif_tc']);
    unset($_SESSION['aktif_personel']);
}

// Aktif TC numarasını yönet (sadece anasayfa dışındaki sayfalarda)
$tc = $_GET['tc'] ?? $_GET['tc_search'] ?? $_SESSION['aktif_tc'] ?? '';
if (!empty($tc) && $currentPage !== 'dashboard_Anasayfa.php') {
    $_SESSION['aktif_tc'] = $tc;
}

// Arama formu action
$formAction = ($currentPage === 'dashboard_Anasayfa.php') ? 'kimlik_bilgileri.php' : $currentPage;

// Collapse açma kontrolü: aktif sayfa hangi menüdeyse collapse açık olacak
$collapseMap = [
    'genelBilgiler' => ['kimlik_bilgileri.php', 'adres_bilgileri.php'],
    'gorevBilgileri' => ['gorev_kaydi.php', 'sozlesme_kaydi.php', 'gorevlendirme.php', 'engellilik_gorev.php'],
    'bilgigirisi' => ['nufus_cuzdani.php', 'ogrenim_bilgileri.php', 'formasyon_sertifika.php', 'borclanma.php', 'engellilik_bilgileri.php', 'yas_tashihi.php'],
    'izinislemleri' => ['ayliksiz_izin.php', 'saglik_refakat.php'],
    'mebdenayrilmaaciksure' => ['meb_ayrilma.php', 'gorevden_uzaklastirma.php'],
    'odulvecezaislemleri' => ['odul.php', 'ceza.php'],
    'digerislemler' => ['bakmakla_yukumlu.php', 'sicil_dosyasi.php'],
    'mebdisihizmetler' => ['kamu.php', 'ssk_kamu.php', 'diger_kurumlar.php'],
    'kadroIslemleri' => ['kadro_kaydi.php']  // YENİ EKLENDI
];

// Hangi collapse açılacak
$openCollapse = '';
foreach ($collapseMap as $collapse => $pages) {
    if (in_array($currentPage, $pages)) {
        $openCollapse = $collapse;
        break;
    }
}
?>



<!-- Sol Menü -->
<div class="sidebar">
    <div class="sidebar-brand">
        <i class="bi bi-people-fill"></i> Arama Menüsü
    </div>

    <!-- ARAMA -->
    <div class="sidebar-search">
        <form id="searchForm" class="input-group" method="get" action="<?= htmlspecialchars($formAction) ?>">
			<input type="text"
				   id="tcInput"
				   name="tc_search"
				   class="form-control"
				   placeholder="T.C. Kimlik No ile ara"
				   maxlength="11"
				   pattern="[0-9]{11}"
				   title="11 haneli TC Kimlik No"
				   value=""
				   autocomplete="off">
	   
            <input type="hidden" name="referer" value="<?= htmlspecialchars($currentPage) ?>">

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-search"></i> ARA
            </button>
        </form>
        <div id="searchResults" class="search-results"></div>
    </div>

	<!-- MENÜ BAŞLIYOR -->
	<div class="list-group list-group-flush">

		<a href="personel_ekle.php" class="list-group-item personel-ekle-link <?= $currentPage === 'personel_ekle.php' ? 'active' : '' ?>">
			<i class="bi bi-person-plus-fill"></i> Personel Ekle
		</a>

		<!-- GENEL BİLGİLER -->
		<a class="list-group-item d-flex justify-content-between align-items-center require-tc"
		   data-bs-toggle="collapse" href="#genelBilgiler" role="button" aria-expanded="<?= $openCollapse === 'genelBilgiler' ? 'true' : 'false' ?>"
		   onclick="return checkTCRequired(event)">
			<span><i class="bi bi-person-vcard"></i> Genel Bilgiler</span>
			<i class="bi bi-caret-down"></i>
		</a>
		<div class="collapse <?= $openCollapse === 'genelBilgiler' ? 'show' : '' ?>" id="genelBilgiler">
			<a href="kimlik_bilgileri.php" class="list-group-item ps-4 require-tc <?= $currentPage === 'kimlik_bilgileri.php' ? 'active' : '' ?>" onclick="return checkTCRequired(event)">
				<i class="bi bi-card-text"></i> Kimlik Bilgileri
			</a>
			<a href="adres_bilgileri.php" class="list-group-item ps-4 require-tc <?= $currentPage === 'adres_bilgileri.php' ? 'active' : '' ?>" onclick="return checkTCRequired(event)">
				<i class="bi bi-geo-alt"></i> Adres Bilgileri
			</a>
		</div>

		<!-- GÖREV BİLGİLERİ -->
		<a class="list-group-item d-flex justify-content-between align-items-center require-tc"
		   data-bs-toggle="collapse" href="#gorevBilgileri" role="button" aria-expanded="<?= $openCollapse === 'gorevBilgileri' ? 'true' : 'false' ?>"
		   onclick="return checkTCRequired(event)">
			<span><i class="bi bi-briefcase"></i> Görev Bilgileri</span>
			<i class="bi bi-caret-down"></i>
		</a>
		<div class="collapse <?= $openCollapse === 'gorevBilgileri' ? 'show' : '' ?>" id="gorevBilgileri">
			<a href="gorev_kaydi.php" class="list-group-item ps-4 require-tc <?= $currentPage === 'gorev_kaydi.php' ? 'active' : '' ?>" onclick="return checkTCRequired(event)"><i class="bi bi-journal-text"></i> Görev Kaydı</a>
			<a href="sozlesme_kaydi.php" class="list-group-item ps-4 require-tc <?= $currentPage === 'sozlesme_kaydi.php' ? 'active' : '' ?>" onclick="return checkTCRequired(event)"><i class="bi bi-file-earmark-text"></i> Sözleşme Kaydı</a>
			<a href="gorevlendirme.php" class="list-group-item ps-4 require-tc <?= $currentPage === 'gorevlendirme.php' ? 'active' : '' ?>" onclick="return checkTCRequired(event)"><i class="bi bi-person-check"></i> Görevlendirme</a>
			<a href="engellilik_gorev.php" class="list-group-item ps-4 require-tc <?= $currentPage === 'engellilik_gorev.php' ? 'active' : '' ?>" onclick="return checkTCRequired(event)"><i class="bi bi-heart-pulse"></i> Engellilik Bilgileri (Görev)</a>
		</div>

		<!-- BİLGİ GİRİŞİ -->
		<a class="list-group-item d-flex justify-content-between align-items-center require-tc"
		   data-bs-toggle="collapse" href="#bilgigirisi" role="button" aria-expanded="<?= $openCollapse === 'bilgigirisi' ? 'true' : 'false' ?>"
		   onclick="return checkTCRequired(event)">
			<span><i class="bi bi-pencil-square"></i> Bilgi Girişi</span>
			<i class="bi bi-caret-down"></i>
		</a>
		<div class="collapse <?= $openCollapse === 'bilgigirisi' ? 'show' : '' ?>" id="bilgigirisi">
			<a href="nufus_cuzdani.php" class="list-group-item ps-4 require-tc <?= $currentPage === 'nufus_cuzdani.php' ? 'active' : '' ?>" onclick="return checkTCRequired(event)"><i class="bi bi-person-badge"></i> Nüfus Cüzdanı Bilgileri</a>
			
			<!-- ÖĞRENİM BİLGİLERİ - GÜNCELLENDİ -->
			<a href="personel_ogrenim.php" class="list-group-item ps-4 require-tc <?= $currentPage === 'personel_ogrenim.php' ? 'active' : '' ?>" onclick="return checkTCRequired(event)">
				<i class="bi bi-journal-bookmark-fill"></i> Öğrenim Bilgileri
			</a>
			
			<a href="formasyon_sertifika.php" class="list-group-item ps-4 require-tc <?= $currentPage === 'formasyon_sertifika.php' ? 'active' : '' ?>" onclick="return checkTCRequired(event)"><i class="bi bi-award"></i> Formasyon/Sertifika Bilgileri</a>
			<a href="borclanma.php" class="list-group-item ps-4 require-tc <?= $currentPage === 'borclanma.php' ? 'active' : '' ?>" onclick="return checkTCRequired(event)"><i class="bi bi-cash-coin"></i> Borçlanma Bilgileri</a>
			<a href="engellilik_bilgileri.php" class="list-group-item ps-4 require-tc <?= $currentPage === 'engellilik_bilgileri.php' ? 'active' : '' ?>" onclick="return checkTCRequired(event)"><i class="bi bi-wheelchair"></i> Engellilik Bilgileri</a>
			<a href="yas_tashihi.php" class="list-group-item ps-4 require-tc <?= $currentPage === 'yas_tashihi.php' ? 'active' : '' ?>" onclick="return checkTCRequired(event)"><i class="bi bi-person-lines-fill"></i> Ad Soyad Yaş Tashihi Bilgileri</a>
		</div>

		<!-- İZİN İŞLEMLERİ -->
		<a class="list-group-item d-flex justify-content-between align-items-center require-tc"
		   data-bs-toggle="collapse" href="#izinislemleri" role="button" aria-expanded="<?= $openCollapse === 'izinislemleri' ? 'true' : 'false' ?>"
		   onclick="return checkTCRequired(event)">
			<span><i class="bi bi-calendar-event"></i> İzin İşlemleri</span>
			<i class="bi bi-caret-down"></i>
		</a>
		<div class="collapse <?= $openCollapse === 'izinislemleri' ? 'show' : '' ?>" id="izinislemleri">
			<a href="ayliksiz_izin.php" class="list-group-item ps-4 require-tc <?= $currentPage === 'ayliksiz_izin.php' ? 'active' : '' ?>" onclick="return checkTCRequired(event)"><i class="bi bi-calendar-x"></i> Aylıksız İzinler</a>
			<a href="saglik_refakat.php" class="list-group-item ps-4 require-tc <?= $currentPage === 'saglik_refakat.php' ? 'active' : '' ?>" onclick="return checkTCRequired(event)"><i class="bi bi-heart"></i> Sağlık ve Refakat İzin</a>
		</div>

		<!-- DİĞERLERİ -->
		<a href="kadro_kaydi.php" class="list-group-item require-tc <?= $currentPage === 'kadro_kaydi.php' ? 'active' : '' ?>" onclick="return checkTCRequired(event)">
			<i class="bi bi-star"></i> Kadro Kaydı
		</a>

		<a href="emeklilik_islemleri.php" class="list-group-item require-tc <?= $currentPage === 'emeklilik_islemleri.php' ? 'active' : '' ?>" onclick="return checkTCRequired(event)">
			<i class="bi bi-bank"></i> Emeklilik İşlemleri
		</a>

		<!-- MEB'DEN AYRILMA -->
		<a class="list-group-item d-flex justify-content-between align-items-center require-tc"
		   data-bs-toggle="collapse" href="#mebdenayrilmaaciksure" role="button" aria-expanded="<?= $openCollapse === 'mebdenayrilmaaciksure' ? 'true' : 'false' ?>"
		   onclick="return checkTCRequired(event)">
			<span><i class="bi bi-box-arrow-right"></i> MEB'den Ayrılma-Açık Süre İşlemi</span>
			<i class="bi bi-caret-down"></i>
		</a>
		<div class="collapse <?= $openCollapse === 'mebdenayrilmaaciksure' ? 'show' : '' ?>" id="mebdenayrilmaaciksure">
			<a href="meb_ayrilma.php" class="list-group-item ps-4 require-tc <?= $currentPage === 'meb_ayrilma.php' ? 'active' : '' ?>" onclick="return checkTCRequired(event)">
				<i class="bi bi-door-open"></i> MEB'den Ayrılma İşlemi
			</a>
			<a href="gorevden_uzaklastirma.php" class="list-group-item ps-4 require-tc <?= $currentPage === 'gorevden_uzaklastirma.php' ? 'active' : '' ?>" onclick="return checkTCRequired(event)">
				<i class="bi bi-shield-exclamation"></i> Görevden Uzaklaştırma Bilgileri
			</a>
		</div>

		<!-- ÖDÜL/CEZA -->
		<a class="list-group-item d-flex justify-content-between align-items-center require-tc"
		   data-bs-toggle="collapse" href="#odulvecezaislemleri" role="button" aria-expanded="<?= $openCollapse === 'odulvecezaislemleri' ? 'true' : 'false' ?>"
		   onclick="return checkTCRequired(event)">
			<span><i class="bi bi-award"></i> Ödül Kaydı İşlemleri</span>
			<i class="bi bi-caret-down"></i>
		</a>
		<div class="collapse <?= $openCollapse === 'odulvecezaislemleri' ? 'show' : '' ?>" id="odulvecezaislemleri">
			<a href="odul.php" class="list-group-item ps-4 require-tc <?= $currentPage === 'odul.php' ? 'active' : '' ?>" onclick="return checkTCRequired(event)"><i class="bi bi-trophy"></i> Ödül Kaydı</a>
			<a href="ceza.php" class="list-group-item ps-4 require-tc <?= $currentPage === 'ceza.php' ? 'active' : '' ?>" onclick="return checkTCRequired(event)"><i class="bi bi-shield-slash"></i> Ceza Kaydı</a>
		</div>

		<!-- DİĞER İŞLEMLER -->
		<a class="list-group-item d-flex justify-content-between align-items-center require-tc"
		   data-bs-toggle="collapse" href="#digerislemler" role="button" aria-expanded="<?= $openCollapse === 'digerislemler' ? 'true' : 'false' ?>"
		   onclick="return checkTCRequired(event)">
			<span><i class="bi bi-three-dots"></i> Diğer İşlemler</span>
			<i class="bi bi-caret-down"></i>
		</a>
		<div class="collapse <?= $openCollapse === 'digerislemler' ? 'show' : '' ?>" id="digerislemler">
			<a href="bakmakla_yukumlu.php" class="list-group-item ps-4 require-tc <?= $currentPage === 'bakmakla_yukumlu.php' ? 'active' : '' ?>" onclick="return checkTCRequired(event)"><i class="bi bi-people"></i> Bakmakla Yükümlü Olduğu Kişiler</a>
			<a href="sicil_dosyasi.php" class="list-group-item ps-4 require-tc <?= $currentPage === 'sicil_dosyasi.php' ? 'active' : '' ?>" onclick="return checkTCRequired(event)"><i class="bi bi-folder"></i> Sicil Dosyası İsteme-Gönderme</a>
		</div>

		<a href="askerlik_islemleri.php" class="list-group-item require-tc <?= $currentPage === 'askerlik_islemleri.php' ? 'active' : '' ?>" onclick="return checkTCRequired(event)"><i class="bi bi-shield"></i> Askerlik İşlemleri</a>

		<!-- MEB DIŞI HİZMETLER -->
		<a class="list-group-item d-flex justify-content-between align-items-center require-tc"
		   data-bs-toggle="collapse" href="#mebdisihizmetler" role="button" aria-expanded="<?= $openCollapse === 'mebdisihizmetler' ? 'true' : 'false' ?>"
		   onclick="return checkTCRequired(event)">
			<span><i class="bi bi-building"></i> MEB Dışı Hizmetler</span>
			<i class="bi bi-caret-down"></i>
		</a>
		<div class="collapse <?= $openCollapse === 'mebdisihizmetler' ? 'show' : '' ?>" id="mebdisihizmetler">
			<a href="kamu.php" class="list-group-item ps-4 require-tc <?= $currentPage === 'kamu.php' ? 'active' : '' ?>" onclick="return checkTCRequired(event)"><i class="bi bi-building"></i> Kamu</a>
			<a href="ssk_kamu.php" class="list-group-item ps-4 require-tc <?= $currentPage === 'ssk_kamu.php' ? 'active' : '' ?>" onclick="return checkTCRequired(event)"><i class="bi bi-building"></i> SSK Kamu</a>
			<a href="diger_kurumlar.php" class="list-group-item ps-4 require-tc <?= $currentPage === 'diger_kurumlar.php' ? 'active' : '' ?>" onclick="return checkTCRequired(event)"><i class="bi bi-buildings"></i> Diğer Kurumlar</a>
		</div>
		
		<!-- DOSYA TAKİBİ (YENİ) -->
		<a class="list-group-item d-flex justify-content-between align-items-center require-tc"
		   data-bs-toggle="collapse" href="#dosyaTakibi" role="button" aria-expanded="<?= $openCollapse === 'dosyaTakibi' ? 'true' : 'false' ?>"
		   onclick="return checkTCRequired(event)">
			<span><i class="bi bi-folder2-open"></i> Dosya Takibi</span>
			<i class="bi bi-caret-down"></i>
		</a>
		<div class="collapse <?= $openCollapse === 'dosyaTakibi' ? 'show' : '' ?>" id="dosyaTakibi">
			<a href="dosya_gonderme.php" class="list-group-item ps-4 require-tc <?= $currentPage === 'dosya_gonderme.php' ? 'active' : '' ?>" onclick="return checkTCRequired(event)">
				<i class="bi bi-send"></i> Dosya Gönderme
			</a>
			<a href="dosya_teslim_alma.php" class="list-group-item ps-4 require-tc <?= $currentPage === 'dosya_teslim_alma.php' ? 'active' : '' ?>" onclick="return checkTCRequired(event)">
				<i class="bi bi-inbox"></i> Dosya Teslim Alma
			</a>
			<a href="dosya_takip.php" class="list-group-item ps-4 require-tc <?= $currentPage === 'dosya_takip.php' ? 'active' : '' ?>" onclick="return checkTCRequired(event)">
				<i class="bi bi-clock-history"></i> Dosya Takibi
			</a>
		</div>
		
	</div>
</div>



<!-- Global değişkenleri JavaScript'e aktar -->
<script>
// PHP değişkenlerini JavaScript'e aktar
window.aktifTc = "<?= htmlspecialchars($_SESSION['aktif_tc'] ?? '') ?>";
window.currentPage = "<?= htmlspecialchars($currentPage) ?>";
</script>