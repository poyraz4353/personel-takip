<?php
/**
 * Personel Takip Sistemi - Sidebar
 * 
 * Bu dosya, personellerin kimlik bilgilerini yönetmektedir.
 * 
 * SİSTEM MİMARİSİ:
 * - 
 * - 
 * - 

 * 
 * @version 1.1
 * @author Fatih
 * @license MIT
 */

// Aktif TC numarasını yönet
$tc = $_GET['tc'] ?? $_GET['tc_search'] ?? $_SESSION['aktif_tc'] ?? '';
if (!empty($tc)) {
    $_SESSION['aktif_tc'] = $tc;
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>



<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Personel Takip Sistemi') ?></title>></head>

<!-- Sol Menü -->
<div class="sidebar">

	<div class="sidebar-brand">
		<i class="bi bi-people-fill"></i> Arama Menüsü
	</div>
    <!-- ARAMA -->
    <div class="sidebar-search">
        <form id="searchForm" class="input-group" method="get">
            <!-- action attribute'u kaldırıldı, JavaScript ile dinamik olarak ayarlanacak -->
            <input type="text" id="tcInput" name="tc_search" class="form-control" placeholder="T.C. Kimlik No ile ara" required>
            <button type="submit" class="btn btn-primary">ARA</button>
        </form>
        <div id="searchResults" class="search-results"></div>
    </div>

    <div class="list-group list-group-flush">
        <!-- GENEL BİLGİLER -->
        <a href="personel_ekle.php" class="list-group-item"><i class="bi bi-star"></i> Personel Ekle</a>
        <a class="list-group-item d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#genelBilgiler">
            <span><i class="bi bi-person-vcard"></i> Genel Bilgiler</span><i class="bi bi-caret-down"></i>
        </a>
        <div class="collapse" id="genelBilgiler">
            <a href="kimlik_bilgileri.php" class="list-group-item ps-4 <?= basename($_SERVER['PHP_SELF']) == 'kimlik_bilgileri.php' ? 'active' : '' ?>">
               <i class="bi bi-card-text"></i> Kimlik Bilgileri
            </a>
            <a href="#" class="list-group-item ps-4"><i class="bi bi-geo-alt"></i> Adres Bilgileri</a>
        </div>
        
        <!-- GÖREV BİLGİLERİ -->
        <a class="list-group-item d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#gorevBilgileri">
            <span><i class="bi bi-briefcase"></i> Görev Bilgileri</span><i class="bi bi-caret-down"></i>
        </a>
        <div class="collapse" id="gorevBilgileri">
            <a href="#" class="list-group-item ps-4"><i class="bi bi-journal-text"></i> Görev Kaydı</a>
            <a href="#" class="list-group-item ps-4"><i class="bi bi-file-earmark-text"></i> Sözleşme Kaydı</a>
            <a href="#" class="list-group-item ps-4"><i class="bi bi-person-check"></i> Görevlendirme</a>
            <a href="#" class="list-group-item ps-4"><i class="bi bi-heart-pulse"></i> Engellilik Bilgileri</a>
        </div>

        <!-- BİLGİ GİRİŞİ -->
        <a class="list-group-item d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#bilgigirisi">
            <span><i class="bi bi-pencil-square"></i> Bilgi Girişi</span><i class="bi bi-caret-down"></i>
        </a>
        <div class="collapse" id="bilgigirisi">
            <a href="#" class="list-group-item ps-4"><i class="bi bi-person-badge"></i> Nüfus Cüzdanı Bilgileri</a>
            <a href="#" class="list-group-item ps-4"><i class="bi bi-book"></i> Öğrenim Bilgileri</a>
            <a href="#" class="list-group-item ps-4"><i class="bi bi-award"></i> Formasyon/Sertifika Bilgileri</a>
            <a href="#" class="list-group-item ps-4"><i class="bi bi-cash-coin"></i> Borçlanma Bilgileri</a>
            <a href="#" class="list-group-item ps-4"><i class="bi bi-wheelchair"></i> Engellilik Bilgileri</a>
            <a href="#" class="list-group-item ps-4"><i class="bi bi-person-lines-fill"></i> Ad Soyad Yaş Tashihi Bilgileri</a>
        </div>

        <!-- İZİN İŞLEMLERİ -->
        <a class="list-group-item d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#izinislemleri">
            <span><i class="bi bi-calendar-event"></i> İzin İşlemleri</span><i class="bi bi-caret-down"></i>
        </a>
        <div class="collapse" id="izinislemleri">
            <a href="#" class="list-group-item ps-4"><i class="bi bi-calendar-x"></i> Aylıksız İzinler</a>
            <a href="#" class="list-group-item ps-4"><i class="bi bi-heart"></i> Sağlık ve Refakat İzin</a>
        </div>

        <!-- DİĞERLERİ -->
        <a href="#" class="list-group-item"><i class="bi bi-star"></i> Kadro Kaydı</a>
        <a href="#" class="list-group-item"><i class="bi bi-bank"></i> Emeklilik İşlemleri</a>

        <!-- MEB'DEN AYRILMA -->
        <a class="list-group-item d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#mebdenayrilmaaciksure">
            <span><i class="bi bi-box-arrow-right"></i> MEB'den Ayrılma-Açık Süre İşlemi</span><i class="bi bi-caret-down"></i>
        </a>
        <div class="collapse" id="mebdenayrilmaaciksure">
            <a href="#" class="list-group-item ps-4"><i class="bi bi-door-open"></i> MEB'den Ayrılma İşlemi</a>
            <a href="#" class="list-group-item ps-4"><i class="bi bi-shield-exclamation"></i> Görevden Uzaklaştırma Bilgileri</a>
        </div>

        <!-- ÖDÜL/CEZA -->
        <a class="list-group-item d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#odulvecezaislemleri">
            <span><i class="bi bi-award"></i> Ödül Kaydı İşlemleri</span><i class="bi bi-caret-down"></i>
        </a>
        <div class="collapse" id="odulvecezaislemleri">
            <a href="#" class="list-group-item ps-4"><i class="bi bi-trophy"></i> Ödül Kaydı</a>
            <a href="#" class="list-group-item ps-4"><i class="bi bi-shield-slash"></i> Ceza Kaydı</a>
        </div>

        <!-- DİĞER İŞLEMLER -->
        <a class="list-group-item d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#digerislemler">
            <span><i class="bi bi-three-dots"></i> Diğer İşlemler</span><i class="bi bi-caret-down"></i>
        </a>
        <div class="collapse" id="digerislemler">
            <a href="#" class="list-group-item ps-4"><i class="bi bi-people"></i> Bakmakla Yükümlü Olduğu Kişiler</a>
            <a href="#" class="list-group-item ps-4"><i class="bi bi-folder"></i> Sicil Dosyası İsteme-Gönderme</a>
        </div>

        <a href="#" class="list-group-item"><i class="bi bi-shield"></i> Askerlik İşlemleri</a>

        <a class="list-group-item d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#mebdisihizmetler">
            <span><i class="bi bi-building"></i> MEB Dışı Hizmetler</span><i class="bi bi-caret-down"></i>
        </a>
        <div class="collapse" id="mebdisihizmetler">
            <a href="#" class="list-group-item ps-4"><i class="bi bi-building"></i> Kamu</a>
            <a href="#" class="list-group-item ps-4"><i class="bi bi-building"></i> SSK Kamu</a>
            <a href="#" class="list-group-item ps-4"><i class="bi bi-buildings"></i> Diğer Kurumlar</a>
        </div>		
    </div>
</div>

<style>
/* ===== SIDEBAR BÖLÜMÜ ===== */
.sidebar {
    min-height: calc(100vh - var(--header-height)); /* Header hariç tüm yükseklik */
    width: var(--sidebar-width);
    background-color: var(--sidebar-bg);
    border-right: 1px solid var(--sidebar-border);
    position: fixed; 
    top: var(--header-height); /* Header'ın altından başla */
    left: 0;
    overflow-y: auto;
    z-index: 999;
}


.sidebar-brand {
    background-color: var(--meb-mavi); /* Mavi yapıldı */
    color: white; 
    text-align: left;
    padding: 15px 20px; 
    margin: 0; 
    font-size: 19px;
    border-bottom: 2px solid #004085; /* Daha koyu mavi sınır (isteğe göre değiştirilebilir) */
    line-height: 21px;
}


/* Sidebar Arama Bölümü */
.sidebar-search { 
    padding: 15px; 
    margin: 0; 
    background: rgba(255, 255, 255, 0.1);
    border-bottom: 1px solid var(--sidebar-border);
}

.sidebar-search .input-group { 
    display: flex; 
    width: 100%; 
}

.sidebar-search .form-control {
    flex: 1; 
    height: 38px; 
    padding: 6px 12px; 
    border: 1px solid rgba(255, 255, 255, 0.2); 
    border-right: none;
    font-size: 14px; 
    border-radius: 4px 0 0 4px;
    background-color: rgba(255, 255, 255, 0.9);
}

.sidebar-search .btn {
    height: 38px; 
    padding: 0 15px; 
    border: 1px solid var(--meb-mavi); 
    background-color: var(--meb-mavi);
    color: white; 
    font-size: 14px; 
    border-radius: 0 4px 4px 0; 
    cursor: pointer;
    transition: background-color 0.2s;
}

.sidebar-search .btn:hover { 
    background-color: #0056b3; 
}

.sidebar-search .btn-clear {
    background-color: #6c757d;
    border-color: #6c757d;
    margin-left: 5px;
}

.sidebar-search .btn-home {
    background-color: var(--ana-bordo);
    border-color: var(--ana-bordo);
    margin-left: 5px;
}

.sidebar-search .btn-home:hover {
    background-color: var(--ana-bordo-koyu);
    border-color: var(--ana-bordo-koyu);
}

/* Sidebar Menü Öğeleri */
.list-group-item {
    color: var(--sidebar-text); 
    background-color: transparent; 
    border: none; 
    border-bottom: 1px solid var(--sidebar-border);
    font-size: 14px; 
    cursor: pointer; 
    display: flex; 
    align-items: center;
    padding: 12px 15px;
    transition: all 0.2s;
}

.list-group-item i { 
    margin-right: 10px; 
    color: var(--sidebar-text); 
    width: 20px;
    text-align: center;
}

.list-group-item:hover { 
    background-color: var(--sidebar-hover); 
    color: #fff; 
}

.list-group-item:hover i { 
    color: #fff; 
}

.list-group-item.active {
    background-color: var(--meb-mavi);
    color: #fff;
    font-weight: 600;
}

.list-group-item.active i {
    color: #fff;
}

.list-group-item[aria-expanded="true"] {
    background-color: var(--sidebar-hover);
    font-weight: 600;
}

.list-group-item[aria-expanded="true"] i.bi-caret-down {
    transform: rotate(180deg);
    transition: transform 0.3s;
}

/* Alt Menü Öğeleri */
.collapse .list-group-item {
    padding-left: 45px;
    font-size: 13.5px;
    background-color: rgba(0, 0, 0, 0.1);
}

.collapse .list-group-item i {
    font-size: 12px;
    margin-right: 12px;
}

.collapse .list-group-item:hover {
    background-color: var(--sidebar-hover);
}

.collapse .list-group-item.active {
    background-color: var(--meb-mavi);
    color: #fff;
    font-weight: 600;
}

/* ===== ARAMA SONUÇLARI ===== */
.search-results {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 5px;
    margin-top: 5px;
    display: none;
    position: absolute;
    width: calc(100% - 30px);
    background: white;
    z-index: 1000;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.search-results .list-group-item {
    padding: 10px 15px;
    border-bottom: 1px solid #eee;
    color: #212529;
}

.search-results .list-group-item:hover {
    background-color: #f8f9fa;
    color: #212529;
}

.tc-result {
    font-weight: bold;
    color: #495057;
}

.name-result {
    color: #6c757d;
}
</style>





