<?php
// DEBUG: Tüm hataları göster
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Tüm istekleri logla
$log_file = __DIR__ . '/debug_log.txt';
file_put_contents($log_file, date('Y-m-d H:i:s') . " - " . $_SERVER['REQUEST_METHOD'] . " - " . ($_SERVER['QUERY_STRING'] ?? '') . "\n", FILE_APPEND);

// POST ise verileri logla
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    file_put_contents($log_file, "POST DATA: " . print_r($_POST, true) . "\n", FILE_APPEND);
    file_put_contents($log_file, "FILES DATA: " . print_r($_FILES, true) . "\n", FILE_APPEND);
}

// AJAX ise sonucu logla
if (isset($_GET['ajax'])) {
    file_put_contents($log_file, "AJAX: " . $_GET['ajax'] . "\n", FILE_APPEND);
}

session_start();

// Tarihi MySQL formatına çevir (d.m.Y -> Y-m-d)
function tarihiMySQLEevir($tarih) {
    if (empty($tarih) || $tarih === 'gg.aa.yyyy') return null;
    
    // d.m.Y formatını kontrol et
    $parcala = explode('.', $tarih);
    if (count($parcala) === 3) {
        $gun = trim($parcala[0]);
        $ay = trim($parcala[1]);
        $yil = trim($parcala[2]);
        
        // Geçerli tarih mi?
        if (checkdate($ay, $gun, $yil)) {
            return "$yil-$ay-$gun";
        }
    }
    return null;
}

// ✅ BAŞARI MESAJI KONTROLÜ
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $_SESSION['success_message'] = 'Personel başarıyla eklendi.';
    header('Location: personel_ekle.php');
    exit;
}

// ✅ FORM VERİLERİNİ SESSION'DA SAKLA
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['form_data'] = $_POST;
    
    // ✅ DİNAMİK DROPDOWN VERİLERİNİ DE SAKLA
    $_SESSION['form_data']['gorev_il_id'] = $_POST['gorev_il_id'] ?? '';
    $_SESSION['form_data']['gorev_ilce_id'] = $_POST['gorev_ilce_id'] ?? '';
    $_SESSION['form_data']['gorev_okul_id'] = $_POST['gorev_okul_id'] ?? '';
    $_SESSION['form_data']['hizmet_sinifi'] = $_POST['hizmet_sinifi'] ?? '';
    $_SESSION['form_data']['kadro_unvani'] = $_POST['kadro_unvani'] ?? '';
    $_SESSION['form_data']['gorev_unvani'] = $_POST['gorev_unvani'] ?? '';
}

// GET isteğinde ve başarı mesajı varsa form verilerini temizle
if ($_SERVER['REQUEST_METHOD'] === 'GET' || isset($_SESSION['success_message'])) {
    unset($_SESSION['form_data']);
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

$db = Database::getInstance();

// ✅ BAŞARI MESAJI KONTROLÜ - Form temizleme için
if (isset($_SESSION['success_message'])) {
    $mesaj = 'success:' . $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// CSRF token oluştur (yoksa)
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Hata mesajı değişkeni
$mesaj = $mesaj ?? null;

// İlleri veritabanından çek
$sorgu = $db->query("SELECT id, il_adi FROM iller ORDER BY il_adi");
$iller = $sorgu->fetchAll(PDO::FETCH_ASSOC);

// FORM GÖNDERİM İŞLEMLERİ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // CSRF doğrulama - Güvenlik için
    if (!checkCSRF($_POST['csrf_token'] ?? '')) {
        $mesaj = 'Geçersiz oturum doğrulaması.';
        $_SESSION['form_data'] = array_merge($_SESSION['form_data'] ?? [], $_POST);
    } else {
        
        // TÜM FORM VERİLERİNİ GÜVENLİ ŞEKİLDE TEMİZLE
        $cleanData = [];
        foreach ($_POST as $key => $value) {
            if (is_array($value)) {
                $cleanData[$key] = array_map('sanitizeInput', $value);
            } else {
                $cleanData[$key] = sanitizeInput($value);
            }
        }

        // ✅ TARİHLERİ MYSQL FORMATINA ÇEVİR
        $tarihAlanlari = ['kurum_baslama_tarihi'];
        
        foreach ($tarihAlanlari as $alan) {
            if (isset($cleanData[$alan])) {
                $cleanData[$alan] = tarihiMySQLEevir($cleanData[$alan]);
            }
        }

        // TC Kimlik No doğrulama - Matematiksel kontrol
        if (!empty($tc_no) && !validateTCNo($tc_no)) {
            $mesaj = 'Geçersiz TC Kimlik Numarası!';
            $_SESSION['form_data'] = array_merge($_SESSION['form_data'] ?? [], $_POST);
        } else {
            
            // Aynı TC ile kayıtlı kişi var mı? - Çift kayıt önleme
            if (!empty($tc_no)) {
                $stmt = $db->prepare("SELECT id, ad_soyadi FROM personel WHERE tc_no = ?");
                $stmt->execute([$tc_no]);
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $existing = false;
            }

            if ($existing) {
                $mesaj = "Bu TC numarası ile <strong>{$existing['ad_soyadi']}</strong> zaten kayıtlı. Lütfen TC kimlik numarasını kontrol edin.";
                $_SESSION['form_data'] = array_merge($_SESSION['form_data'] ?? [], $_POST);
            } else {
                
                // Transaction başlat
                $db->beginTransaction();

                try {
                    
                    // ID'leri metne dönüştür
                    $hizmet_sinifi_text = !empty($cleanData['hizmet_sinifi']) ? 
                        getHizmetSinifiAdi($cleanData['hizmet_sinifi'], $db) : '';
                    
                    $kadro_unvani_text = !empty($cleanData['kadro_unvani']) ? 
                        getKadroUnvaniAdi($cleanData['kadro_unvani'], $db) : '';
                    
                    $gorev_unvani_text = !empty($cleanData['gorev_unvani']) ? 
                        getGorevUnvaniAdi($cleanData['gorev_unvani'], $db) : '';
                    
                    $yer_degistirme_text = !empty($cleanData['yer_degistirme_cesidi']) ? 
                        getYerDegistirmeCesidiAdi($cleanData['yer_degistirme_cesidi'], $db) : '';

                    $atama_alani_text = !empty($cleanData['atama_alani']) ? 
                        getAtamaAlaniAdi($cleanData['atama_alani'], $db) : '';

                    // 2. GÖREV BİLGİLERİ - personel_gorev tablosuna
                    $sql_gorev = "INSERT INTO personel_gorev (
                        personel_id,
                        istihdam_tipi, 
                        hizmet_sinifi, 
                        kadro_unvani, 
                        gorev_unvani,
                        kariyer_basamagi, 
                        atama_alani, 
                        kurum_baslama_tarihi,
                        durum,
                        yer_degistirme_cesidi, 
                        gorev_aciklama,
                        gorev_il_adi, 
                        gorev_ilce_adi, 
                        gorev_okul_adi, 
                        gorev_kapali_kurum,
                        kayit_tarihi,
                        guncelleme_tarihi
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

                    $stmt_gorev = $db->prepare($sql_gorev);
                    $stmt_gorev->execute([
                        $personel_id,
                        $cleanData['istihdam_tipi'] ?? '',
                        $hizmet_sinifi_text,
                        $kadro_unvani_text,
                        $gorev_unvani_text,
                        $cleanData['kariyer_basamagi'] ?? '',
                        $atama_alani_text,
                        $cleanData['kurum_baslama_tarihi'] ?? '',
                        $cleanData['durum'] ?? 'Görevde',
                        $yer_degistirme_text,
                        $cleanData['gorev_aciklama'] ?? '',
                        $gorev_il_adi,
                        $gorev_ilce_adi,
                        $gorev_okul_adi,
                        !empty($cleanData['gorev_kapali_kurum']) ? 1 : 0
                    ]);

                    // Tüm işlemler başarılı - Commit
                    $db->commit();

                    // Başarı mesajını session'a kaydet ve yönlendir
                    $_SESSION['success_message'] = 'Personel başarıyla eklendi.';
                    unset($_SESSION['form_data']);
                    header('Location: personel_ekle.php');
                    exit;

                } catch (Exception $e) {
                    // Hata durumunda rollback
                    $db->rollBack();
                    $_SESSION['form_data'] = array_merge($_SESSION['form_data'] ?? [], $_POST);

                    // Hata mesajını ayarla
                    $errorMessage = 'Kayıt işlemi sırasında bir hata oluştu: ' . $e->getMessage();

                    // Loglama
                    error_log("Personel Ekleme Hatası: " . $e->getMessage());

                    $mesaj = $errorMessage;
                }
            }
        }
    }
}

// AJAX İSTEKLERİNİ İŞLEME - Dinamik dropdown'lar için
if (isset($_GET['ajax'])) {
    // Güvenlik header'ları
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');

    try {
        // AJAX için basitleştirilmiş
        $db = Database::getInstance();

        // AJAX tipini güvenli şekilde al
        $ajaxType = $_GET['ajax'];
        if (!preg_match('/^[a-z_]+$/', $ajaxType)) {
            throw new Exception("Geçersiz AJAX isteği");
        }

        switch ($ajaxType) {

            case 'hizmet_siniflari':
                $stmt = $db->prepare("SELECT id, sinif_adi FROM hizmet_siniflari WHERE durum = 1 ORDER BY id ASC");
                $stmt->execute();
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;

            case 'kadro_unvanlari':
                if (!isset($_GET['hizmet_sinif_id'])) {
                    throw new Exception("Hizmet sınıfı ID gereklidir");
                }
                $hizmetSinifId = filter_var($_GET['hizmet_sinif_id'], FILTER_VALIDATE_INT);
                if (!$hizmetSinifId || $hizmetSinifId < 1) {
                    throw new Exception("Geçersiz hizmet sınıfı ID");
                }
                $stmt = $db->prepare("SELECT id, unvan_adi FROM kadro_unvanlari WHERE hizmet_sinif_id = ? AND durum = 1 ORDER BY id ASC");
                $stmt->execute([$hizmetSinifId]);
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;

            case 'gorev_unvanlari':
                if (!isset($_GET['hizmet_sinif_id'])) {
                    throw new Exception("Hizmet sınıfı ID gereklidir");
                }
                $hizmetSinifId = filter_var($_GET['hizmet_sinif_id'], FILTER_VALIDATE_INT);
                if (!$hizmetSinifId || $hizmetSinifId < 1) {
                    throw new Exception("Geçersiz hizmet sınıfı ID");
                }
                $stmt = $db->prepare("SELECT id, unvan_adi FROM gorev_unvanlari WHERE hizmet_sinif_id = ? AND durum = 1 ORDER BY id ASC");
                $stmt->execute([$hizmetSinifId]);
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;

            case 'durum':
                $stmt = $db->prepare("SELECT id, durum_adi FROM durumu WHERE aktif = 1 ORDER BY id ASC");
                $stmt->execute();
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;

            case 'atama_alani':
                $stmt = $db->prepare("SELECT id, alan_adi FROM atama_alani WHERE durum = 1 ORDER BY id ASC");
                $stmt->execute();
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;

            case 'yer_degistirme_cesidi':
                $stmt = $db->prepare("SELECT id, yer_degistirme_cesidi FROM yer_degistirme_cesidi WHERE durum = 1 ORDER BY id ASC");
                $stmt->execute();
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;

            default:
                throw new Exception("Geçersiz AJAX isteği");
        }
        
    } catch (Exception $e) {
        error_log("AJAX Hatası: " . $e->getMessage());
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }

    exit;
}
?>



<!DOCTYPE html>
<html lang="tr">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Personel Ekle</title>
  
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  
<style>

</style>

</head>



<!-- GENEL SAYFA YAPISI -->
<body>  
  <!-- ANA KAPSAYICI - Tüm içeriği merkezde tutan ana div -->
  <div class="container mt-4">
    
	
      <!-- CSRF Token - Güvenlik için oturum doğrulama -->
      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

      <!-- ✅ OTOMATİK KAYBOLAN MESAJ ALANI -->
      <!-- Başarı veya hata mesajlarını gösteren dinamik bildirim alanı -->
      <?php if (isset($mesaj)): ?>
        <?php
        $alertType = str_starts_with($mesaj, 'success:') ? 'success' : 'error';
        $alertMessage = str_starts_with($mesaj, 'success:') ? substr($mesaj, 8) : $mesaj;
        $alertIcon = $alertType === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
        $alertTitle = $alertType === 'success' ? 'Başarılı!' : 'Hata!';
        ?>
        <div class="alert auto-hide-alert <?= $alertType ?>" id="autoHideAlert" role="alert">
            <div class="alert-icon">
                <i class="fas <?= $alertIcon ?>"></i>
            </div>
            <div class="alert-title"><?= $alertTitle ?></div>
            <div class="alert-message"><?= $alertMessage ?></div>
            <div class="progress-container">
                <div class="progress-bar"></div>
            </div>
        </div>
      <?php endif; ?>
      

	  <!-- FORM DOĞRULAMA UYARISI -->
	  <div id="form-validation-alert" class="alert alert-warning d-none mb-3">
		  <i class="fas fa-exclamation-triangle me-2"></i>
		  <span id="validation-message"></span>
	  </div>
	  
		
          <ul class="nav nav-tabs" id="mainTabs" role="tablist">
                  
            <!-- GÖREV KAYDI SEKME BUTONU - İstihdam ve görev bilgileri -->
            <li class="nav-item" role="presentation">
              <button class="nav-link" data-bs-toggle="tab" data-bs-target="#gorev-kaydi" type="button">
                <i class="fas fa-building me-1"></i> Görev Kaydı
              </button>
            </li>
                        
          </ul>

          <!-- SEKMELERİN İÇERİK ALANLARI -->
          <!-- SEKMELERİN İÇERİK ALANLARI -->
          <!-- Her sekme butonuna karşılık gelen içerik bölümleri -->
          <div class="tab-content" id="mainTabContent">

            <!-- 3. GÖREV KAYDI SEKMESİ -->
            <!-- Personelin görev yeri, istihdam ve görev bilgileri -->
            <div class="tab-pane" id="gorev-kaydi" role="tabpanel">

              <!-- KURUM/OKUL BİLGİLERİ KARTI -->
              <!-- Görev yapılan kurumun iletişim ve tanımlama bilgileri -->
              <div class="section-card kurum-okul">
                <div class="row compact-row">


              <!-- GÖREVE BAŞLAMA BİLGİLERİ KARTI -->
              <!-- Memuriyet ve kurum başlama tarihleri -->
              <div class="section-card gorev-baslama">
                <div class="section-title"><i class="fas fa-calendar-alt"></i> Göreve Başlama Bilgileri</div>
                <div class="row compact-row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label required-field">Kurumda Başlama Tarihi</label>
                      <input type="text" name="kurum_baslama_tarihi" class="form-control datepicker" placeholder="gg.aa.yyyy"
                        value="<?= getFormData('kurum_baslama_tarihi') ?>">
                    </div>
                  </div>
                </div>
              </div>

              <!-- İSTİHDAM BİLGİLERİ KARTI -->
              <!-- Hizmet sınıfı, istihdam tipi ve ünvan bilgileri -->
              <div class="section-card">
                <div class="section-title"><i class="fas fa-archive"></i>İstihdam Bilgileri</div>
                <div class="row compact-row">

                  <!-- HİZMET SINIFI -->
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label required-field">Hizmet Sınıfı</label>
                      <select name="hizmet_sinifi" class="form-select">
                        <option value="">Seçiniz</option>
                        <!-- AJAX ile doldurulacak -->
                      </select>
                    </div>
                  </div>

                  <!-- İSTİHDAM TİPİ -->
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label required-field">İstihdam Tipi</label>
                      <select name="istihdam_tipi" class="form-select">
                        <option value="">Seçiniz</option>
                        <option value="Kadrolu" <?= getFormData('istihdam_tipi') == 'Kadrolu' ? 'selected' : '' ?>>Kadrolu</option>
                        <option value="Sözleşmeli Personel" <?= getFormData('istihdam_tipi') == 'Sözleşmeli Personel' ? 'selected' : '' ?>>Sözleşmeli Personel</option>
                        <option value="Geçici Personel" <?= getFormData('istihdam_tipi') == 'Kurumlarası Görevlendirme(Bakanlık)' ? 'selected' : '' ?>>Kurumlarası Görevlendirme(Bakanlık)</option>
                        <option value="İşçi" <?= getFormData('istihdam_tipi') == 'İşçi' ? 'selected' : '' ?>>İşçi</option>
                        <option value="Geçici Personel" <?= getFormData('istihdam_tipi') == 'Geçici Personel' ? 'selected' : '' ?>>Geçici Personel</option>
                        <option value="Geçici Personel" <?= getFormData('istihdam_tipi') == 'İşçi (696 K.H.K.)' ? 'selected' : '' ?>>İşçi (696 K.H.K.)</option>
                        <option value="Geçici Personel" <?= getFormData('istihdam_tipi') == 'Kurumlarası Görevlendirme(Valilik)' ? 'selected' : '' ?>>Kurumlarası Görevlendirme(Valilik)</option>
                        <option value="Geçici Personel" <?= getFormData('istihdam_tipi') == 'Görevlendirme' ? 'selected' : '' ?>>Görevlendirme</option>
                      </select>
                    </div>
                  </div>

                  <!-- KADRO ÜNVANI -->
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label required-field">Kadro Ünvanı</label>
                      <select name="kadro_unvani" class="form-select">
                        <option value="">Önce hizmet sınıfı seçin</option>
                      </select>
                    </div>
                  </div>

                  <!-- GÖREV ÜNVANI -->
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label required-field">Görev Ünvanı</label>
                      <select name="gorev_unvani" class="form-select">
                        <option value="">Önce hizmet sınıfı seçin</option>
                      </select>
                    </div>
                  </div>

                  <!-- ÖĞRETMENLİK KARİYER BASAMAĞI -->
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">Öğretmenlik Kariyer Basamağı</label>
                      <select name="kariyer_basamagi" class="form-select" disabled>
                        <option value="">Seçiniz</option>
                        <option value="Uzman Öğretmen">Uzman Öğretmen</option>
                        <option value="Başöğretmen">Başöğretmen</option>
                      </select>
                    </div>
                  </div>

                </div>
              </div>

              <!-- GÖREV DETAYLARI KARTI -->
              <!-- Atama alanı ve yer değiştirme çeşidi -->
              <div class="section-card">
                <div class="section-title"><i class="fas fa-tasks"></i>Görev Detayları</div>
                <div class="row compact-row">

                  <!-- BAKANLIK ATAMA ALANI -->
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">Bakanlık Atama Alanı</label>
                      <select name="atama_alani" class="form-select">
                        <option value="">Seçiniz</option>
                      </select>
                    </div>
                  </div>

                  <!-- ATAMA/YERDEĞİŞTİRME ÇEŞİDİ -->
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label required-field">Atama/Yerdeğiştirme Çeşidi</label>
                      <select name="yer_degistirme_cesidi" class="form-select">
                        <option value="">Seçiniz</option>
                      </select>
                    </div>
                  </div>
                </div>
              </div>

            </div>
            <!-- GÖREV KAYDI SEKMESİ SONU -->
            </div>

  </div>
  <!-- ANA KAPSAYICI SONU -->

</body>
<!-- GENEL SAYFA YAPISI SONU -->


	<!-- jQuery ekle (CDN üzerinden) -->
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

	<!-- Bootstrap JS (gerekli) -->
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
	<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/tr.js"></script>

<script>
// ✅ AJAX İstek Yöneticisi - CSRF Token Desteği
function makeAjaxRequest(url, params = {}) {
    // URL'ye CSRF token ekle
    const separator = url.includes('?') ? '&' : '?';
    const urlWithToken = url + separator + 'csrf_token=' + encodeURIComponent('<?= $_SESSION['csrf_token'] ?>');
    
    return fetch(urlWithToken, {
        headers: {
            'X-CSRF-Token': '<?= $_SESSION['csrf_token'] ?>'
        }
    })
    .then(response => {
        if (!response.ok) throw new Error('Network error');
        return response.json();
    });
}

// ✅ Otomatik Kaybolan Mesaj Yönetimi
document.addEventListener('DOMContentLoaded', function() {
    const autoHideAlert = document.getElementById('autoHideAlert');
    
    if (autoHideAlert) {
        // 13 saniye sonra mesajı kaldır
        setTimeout(() => {
            autoHideAlert.classList.add('fade-out');
            setTimeout(() => {
                autoHideAlert.remove();
            }, 500);
        }, 13000);
        
        // Tıklayınca da kapatılabilir
        autoHideAlert.addEventListener('click', function() {
            this.classList.add('fade-out');
            setTimeout(() => {
                this.remove();
            }, 500);
        });
    }
});


// ✅ Takvim Datepicker başlatma
document.addEventListener('DOMContentLoaded', function () {
    function initVisibleDatepickers() {
        document.querySelectorAll('.tab-pane.active .datepicker').forEach(function (input) {
            if (!input._flatpickr) {
                flatpickr(input, {
                    locale: "tr",
                    dateFormat: "d.m.Y",
                    allowInput: true,
                    clickOpens: true,
                    disableMobile: true,
                    position: "below",
                    appendTo: document.body,
                    animate: false,
                    monthSelectorType: "static",
                    prevArrow: '<i class="fas fa-chevron-left"></i>',
                    nextArrow: '<i class="fas fa-chevron-right"></i>',
                    onReady: function (selectedDates, dateStr, instance) {
                        if (!dateStr && instance.input) {
                            instance.input.placeholder = "gg.aa.yyyy";
                        }
                    }
                });
            }
        });
    }

    initVisibleDatepickers();

    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (tabBtn) {
        tabBtn.addEventListener('shown.bs.tab', function () {
            setTimeout(initVisibleDatepickers, 100);
        });
    });
});


// ✅ SAYFA YÜKLENDİĞİNDE DİNAMİK DROPDOWN'LARI YENİDEN YÜKLE
function restoreDynamicDropdowns() {
    console.log('🔄 Dinamik dropdownlar yeniden yükleniyor...');
    
    // Görev ili seçiliyse ilçeleri yükle
    const gorevIlSelect = document.getElementById('gorev_il_id');
    if (gorevIlSelect && gorevIlSelect.value) {
        console.log('📍 Görev ili seçili:', gorevIlSelect.value);
        setTimeout(() => {
            gorevIlSelect.dispatchEvent(new Event('change'));
        }, 300);
    }

    // Hizmet sınıfı seçiliyse bağlı alanları yükle
    const hizmetSinifSelect = document.querySelector('select[name="hizmet_sinifi"]');
    if (hizmetSinifSelect && hizmetSinifSelect.value) {
        console.log('🎯 Hizmet sınıfı seçili:', hizmetSinifSelect.value);
        setTimeout(() => {
            hizmetSinifSelect.dispatchEvent(new Event('change'));
        }, 800);
    }

}

// Sayfa yüklendiğinde dropdown'ları başlat
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Sayfa yüklendi, dropdownlar başlatılıyor...');
    
    // 2 saniye sonra dinamik dropdown'ları restore et
    setTimeout(restoreDynamicDropdowns, 2000);
});


// ✅ HİZMET SINIFI İLE İLGİLİ TÜM İŞLEMLER
document.addEventListener('DOMContentLoaded', function () {
    console.log('🚀 Hizmet sınıfı işlemleri başlatılıyor...');
    
    const hizmetSelect = document.querySelector('select[name="hizmet_sinifi"]');
    const kadroSelect = document.querySelector('select[name="kadro_unvani"]');
    const gorevSelect = document.querySelector('select[name="gorev_unvani"]');
    const istihdamSelect = document.querySelector('select[name="istihdam_tipi"]');
    const kariyerSelect = document.querySelector('select[name="kariyer_basamagi"]');
    const istihdamUyari = document.getElementById('istihdam-uyari');

    if (!hizmetSelect) {
        console.error('❌ Hizmet sınıfı select elementi bulunamadı!');
        return;
    }

    // 3.1 HİZMET SINIFLARINI YÜKLE
    function loadHizmetSiniflari() {
        console.log('🔄 Hizmet sınıfları yükleniyor...');
        
        fetch('personel_ekle.php?ajax=hizmet_siniflari')
            .then(res => {
                if (!res.ok) throw new Error('HTTP error: ' + res.status);
                return res.json();
            })
            .then(data => {
                console.log('✅ Hizmet sınıfları yüklendi:', data);
                
                hizmetSelect.innerHTML = '<option value="">Seçiniz</option>';
                data.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = item.sinif_adi;
                    hizmetSelect.appendChild(option);
                    
                    // Session'daki değeri seç
                    if ('<?= getFormData('hizmet_sinifi') ?>' == item.id) {
                        option.selected = true;
                    }
                });
                
                // Hizmet sınıfı seçiliyse bağlı alanları yükle
                if (hizmetSelect.value) {
                    hizmetSelect.dispatchEvent(new Event('change'));
                }
            })
            .catch(err => {
                console.error('❌ Hizmet sınıfları yüklenemedi:', err);
                hizmetSelect.innerHTML = '<option value="">Yükleme hatası</option>';
            });
    }

    // İSTİHDAM TİPLERİ GRUPLARI
    const istihdamGruplari = {
        'normal': [
            'Kadrolu',
            'Kurumlararası Görevlendirme(Bakanlık)',
            'Kurumlararası Görevlendirme(Valilik)',
            'Görevlendirme'
        ],
        'diger': [
            'Sözleşmeli Personel',
            'İşçi',
            'Geçici Personel',
            'İşçi (696 K.H.K.)'
        ]
    };

    const hizmetSinifiGruplari = {
        'Diğer Statüler': 'diger',
        'Yardımcı Hizmetler': 'diger'
    };

    // İSTİHDAM TİPLERİNİ GÜNCELLE
    function kontrolIstihdamTipleri() {
        const secilenSinif = hizmetSelect.options[hizmetSelect.selectedIndex]?.textContent?.trim();
        console.log('🎯 Hizmet sınıfı değişti:', secilenSinif);
        
        const mevcutSecili = istihdamSelect.value;
        istihdamSelect.innerHTML = '<option value="">Seçiniz</option>';

        const grup = hizmetSinifiGruplari[secilenSinif] || 'normal';
        const uygunTipler = istihdamGruplari[grup];

        console.log('📋 Kullanılacak istihdam tipleri:', uygunTipler);

        uygunTipler.forEach(tip => {
            const option = document.createElement('option');
            option.value = tip;
            option.textContent = tip;
            if (tip === mevcutSecili) option.selected = true;
            istihdamSelect.appendChild(option);
        });

        // Sadece uyarı mesajını göster/gizle, alanları pasifize etme
        if (istihdamSelect.value === 'Sözleşmeli Personel') {
            if (istihdamUyari) istihdamUyari.classList.remove('d-none');
        } else {
            if (istihdamUyari) istihdamUyari.classList.add('d-none');
        }
    }

    // KADRO ÜNVANLARINI YÜKLE
    function loadKadroUnvanlari(hizmetSinifId) {
        if (!hizmetSinifId) {
            if (kadroSelect) kadroSelect.innerHTML = '<option value="">Önce hizmet sınıfı seçin</option>';
            return;
        }

        if (kadroSelect) {
            kadroSelect.innerHTML = '<option value="">Yükleniyor...</option>';
            
            fetch(`personel_ekle.php?ajax=kadro_unvanlari&hizmet_sinif_id=${hizmetSinifId}`)
                .then(res => res.json())
                .then(unvanlar => {
                    kadroSelect.innerHTML = '<option value="">Seçiniz</option>';
                    unvanlar.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.id;
                        option.textContent = item.unvan_adi;
                        kadroSelect.appendChild(option);
                        
                        // Session'daki değeri seç
                        if ('<?= getFormData('kadro_unvani') ?>' == item.id) {
                            option.selected = true;
                        }
                    });
                })
                .catch(err => {
                    console.error('Kadro ünvanları yüklenemedi:', err);
                    kadroSelect.innerHTML = '<option value="">Yükleme hatası</option>';
                });
        }
    }

    // GÖREV ÜNVANLARINI YÜKLE
    function loadGorevUnvanlari(hizmetSinifId) {
        if (!hizmetSinifId) {
            if (gorevSelect) gorevSelect.innerHTML = '<option value="">Önce hizmet sınıfı seçin</option>';
            return;
        }

        if (gorevSelect) {
            gorevSelect.innerHTML = '<option value="">Yükleniyor...</option>';

            fetch(`personel_ekle.php?ajax=gorev_unvanlari&hizmet_sinif_id=${hizmetSinifId}`)
                .then(res => res.json())
                .then(gorevler => {
                    gorevSelect.innerHTML = '<option value="">Seçiniz</option>';
                    gorevler.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.id;
                        option.textContent = item.unvan_adi;
                        gorevSelect.appendChild(option);
                        
                        // Session'daki değeri seç
                        if ('<?= getFormData('gorev_unvani') ?>' == item.id) {
                            option.selected = true;
                        }
                    });
                })
                .catch(err => {
                    console.error('Görev ünvanları yüklenemedi:', err);
                    gorevSelect.innerHTML = '<option value="">Yükleme hatası</option>';
                });
        }
    }

    // KARİYER BASAMAĞI KONTROLÜ
    function kontrolKariyerBasamagi() {
        const selectedText = hizmetSelect.options[hizmetSelect.selectedIndex]?.textContent?.trim();
        
        if (kariyerSelect) {
            if (selectedText === 'Eğitim / Öğretim') {
                kariyerSelect.disabled = false;
                kariyerSelect.style.removeProperty('background-color');
                kariyerSelect.style.removeProperty('color');
            } else {
                kariyerSelect.disabled = true;
                kariyerSelect.value = '';
                kariyerSelect.style.setProperty('background-color', '#e9ecef', 'important');
                kariyerSelect.style.setProperty('color', '#6c757d', 'important');
            }
        }
    }

    // EVENT LISTENER'LAR
    hizmetSelect.addEventListener('change', function() {
        const hizmetSinifId = this.value;
        console.log('🔄 Hizmet sınıfı değişti:', hizmetSinifId);
        
        kontrolIstihdamTipleri();
        kontrolKariyerBasamagi();
        loadKadroUnvanlari(hizmetSinifId);
        loadGorevUnvanlari(hizmetSinifId);
    });

    // İstihdam tipi değiştiğinde - SADECE UYARI MESAJI GÖSTER, ALANLARI PASİFİZE ETME
    istihdamSelect.addEventListener('change', function() {
        const secilenTip = this.value;
        console.log('🔍 İstihdam tipi değişti:', secilenTip);

        // Sadece uyarı mesajını göster/gizle
        if (secilenTip === 'Sözleşmeli Personel') {
            if (istihdamUyari) istihdamUyari.classList.remove('d-none');
        } else {
            if (istihdamUyari) istihdamUyari.classList.add('d-none');
        }
    });

    // SAYFA YÜKLENDİĞİNDE BAŞLAT
    loadHizmetSiniflari();
});

// Validasyon fonksiyonunu da güncelleyelim
function validateGorevBilgileri() {
    const errors = [];
    const istihdamTipi = document.querySelector('select[name="istihdam_tipi"]')?.value;
    
    // Sözleşmeli personel için de aynı validasyon kuralları uygula (alanlar pasif değil artık)
    const requiredGorevFields = [
        { selector: 'input[name="kurum_baslama_tarihi"]', message: 'Kurumda Başlama Tarihi zorunludur.' },
        { selector: 'select[name="hizmet_sinifi"]', message: 'Hizmet Sınıfı zorunludur.' },
        { selector: 'select[name="istihdam_tipi"]', message: 'İstihdam Tipi zorunludur.' },
        { selector: 'select[name="kadro_unvani"]', message: 'Kadro Ünvanı zorunludur.' },
        { selector: 'select[name="gorev_unvani"]', message: 'Görev Ünvanı zorunludur.' },
        { selector: 'select[name="yer_degistirme_cesidi"]', message: 'Yer Değiştirme Çeşidi zorunludur.' }
    ];
    
    requiredGorevFields.forEach(field => {
        const element = document.querySelector(field.selector);
        // Element varsa ve değeri boşsa hata ekle (disabled kontrolü kaldırıldı)
        if (element && !element.value.trim()) {
            errors.push({ 
                message: field.message, 
                element: element, 
                tab: 'gorev-kaydi' 
            });
        }
    });
    
    return errors;
}


//✅ Atama Alanını ve Yer Değiştirme Çeşitlerini yükle
document.addEventListener('DOMContentLoaded', function () {
    const atamaSelect = document.querySelector('select[name="atama_alani"]');
    const yerSelect = document.querySelector('select[name="yer_degistirme_cesidi"]');

    if (atamaSelect) {
        fetch('personel_ekle.php?ajax=atama_alani')
            .then(res => res.json())
            .then(data => {
                atamaSelect.innerHTML = '<option value="">Seçiniz</option>';
                data.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = item.alan_adi;
                    atamaSelect.appendChild(option);
                });
            })
            .catch(err => {
                console.error('Atama alanları yüklenemedi:', err);
                atamaSelect.innerHTML = '<option value="">Yükleme hatası</option>';
            });
    }

    if (yerSelect) {
        fetch('personel_ekle.php?ajax=yer_degistirme_cesidi')
            .then(res => res.json())
            .then(data => {
                yerSelect.innerHTML = '<option value="">Seçiniz</option>';
                data.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = item.yer_degistirme_cesidi;
                    yerSelect.appendChild(option);
                });
            })
            .catch(err => {
                console.error('Yer değiştirme çeşitleri yüklenemedi:', err);
                yerSelect.innerHTML = '<option value="">Yükleme hatası</option>';
            });
    }
});




// ✅ TÜM FORM VALIDASYONLARINI TOPLAYAN ANA FONKSİYON
function validateForm() {
    console.log('🔍 Validasyon çalıştı - GEÇİCİ OLARAK HEP BAŞARILI');
    return []; // TÜM VALİDASYONLARI GEÇ
}


// 3. GÖREV BİLGİLERİ VALIDASYONU
function validateGorevBilgileri() {
    const errors = [];
    const istihdamTipi = document.querySelector('select[name="istihdam_tipi"]')?.value;
    
    // Sözleşmeli personel için görev bilgileri gerekmez
    if (istihdamTipi === 'Sözleşmeli Personel') {
        return errors;
    }
    
    const requiredGorevFields = [
        { selector: 'input[name="kurum_baslama_tarihi"]', message: 'Kurumda Başlama Tarihi zorunludur.' },
		
        { selector: 'select[name="hizmet_sinifi"]', message: 'Hizmet Sınıfı zorunludur.' },
        { selector: 'select[name="istihdam_tipi"]', message: 'İstihdam Tipi zorunludur.' },
        { selector: 'select[name="kadro_unvani"]', message: 'Kadro Ünvanı zorunludur.' },
        { selector: 'select[name="gorev_unvani"]', message: 'Görev Ünvanı zorunludur.' },
        { selector: 'select[name="yer_degistirme_cesidi"]', message: 'Yer Değiştirme Çeşidi zorunludur.' }
    ];
    
    requiredGorevFields.forEach(field => {
        const element = document.querySelector(field.selector);
        if (element && !element.disabled && !element.value.trim()) {
            errors.push({ 
                message: field.message, 
                element: element, 
                tab: 'gorev-kaydi' 
            });
        }
    });
    
    return errors;
}

  </script>

</body>
</html>









