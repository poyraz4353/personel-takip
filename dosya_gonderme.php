<?php
/**
 * DOSYA GÖNDERME - Personel Takip Sistemi - dosya_gonderme.php
 * @version 1.2
 * @author Fatih
 */

require_once __DIR__ . '/config/session_manager.php';
SessionManager::start();
SessionManager::requireAuth();
$username = SessionManager::getUsername();

require_once __DIR__ . '/config/db_config.php';
$database = Database::getInstance();
$db = $database->getConnection();

// CSRF KORUMASI
function generateSimpleToken() {
    if (!isset($_SESSION['simple_token'])) {
        $_SESSION['simple_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['simple_token'];
}

$simpleToken = generateSimpleToken();

$personel = null;
$dosya_listesi = [];
$duzenlenecek_dosya = null;
$tc = $_GET['tc_search'] ?? $_SESSION['aktif_tc'] ?? '';
$personel_id = 0;
$has_bitmemis_gorev = false; // Bitmemiş görev var mı?

$pageTitle = 'Dosya Gönderme';
$pageIcon = 'send';
$welcomeMessage = 'Buradan Personelin Gönderdiği Dosyaları yönetebilirsiniz.';

$gunler = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];
$turkceGun = $gunler[date('w')];

$content = 'includes/dosya_gonderme_content.php';

// TC kontrolü
if (empty($tc)) {
    SessionManager::setMessage('error', 'Lütfen geçerli bir TC Kimlik No ile arama yapınız!');
    header("Location: dashboard_Anasayfa.php");
    exit;
}
if (strlen($tc) !== 11 || !ctype_digit($tc)) {
    SessionManager::setMessage('error', 'Geçerli bir TC Kimlik No giriniz (11 haneli rakam)');
    header("Location: dashboard_Anasayfa.php");
    exit;
}
SessionManager::setAktifTC($tc);

// =============================================================================
// İL-İLÇE-OKUL LİSTELERİ (dosya_gonderme.php içine eklenecek)
// =============================================================================
$iller = $database->fetchAll("SELECT id, il_adi FROM iller ORDER BY il_adi");

// Düzenleme modunda ilçeleri çek
$ilceler = [];
if (isset($duzenlenecek_dosya) && !empty($duzenlenecek_dosya['gittigi_il'])) {
    $il = $database->fetch("SELECT id FROM iller WHERE il_adi = ?", [$duzenlenecek_dosya['gittigi_il']]);
    if ($il) {
        $ilceler = $database->fetchAll("SELECT id, ilce_adi FROM ilceler WHERE il_id = ? ORDER BY ilce_adi", [$il['id']]);
    }
}

// Düzenleme modunda okulları çek
$okullar = [];
if (isset($duzenlenecek_dosya) && !empty($duzenlenecek_dosya['gittigi_ilce']) && !empty($duzenlenecek_dosya['gittigi_il'])) {
    $il = $database->fetch("SELECT id FROM iller WHERE il_adi = ?", [$duzenlenecek_dosya['gittigi_il']]);
    if ($il) {
        $ilce = $database->fetch("SELECT id FROM ilceler WHERE ilce_adi = ? AND il_id = ?", [
            $duzenlenecek_dosya['gittigi_ilce'], 
            $il['id']
        ]);
        if ($ilce) {
            $okullar = $database->fetchAll("SELECT kurum_kodu, gorev_yeri FROM okullar WHERE ilce_id = ? ORDER BY gorev_yeri", [$ilce['id']]);
        }
    }
}

// Personel bul
function findPersonel($db, $search_by, $value) {
    try {
        $sql = $search_by === 'tc' ? "SELECT * FROM personel WHERE tc_no = ?" : "SELECT * FROM personel WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$value]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Personel arama hatası: " . $e->getMessage());
        return null;
    }
}

$personel = findPersonel($db, 'tc', $tc);
if ($personel) {
    $personel_id = (int)$personel['id'];
    SessionManager::setAktifPersonelID($personel_id);
    SessionManager::addToSearchHistory($tc, $personel['ad_soyadi']);
} else {
    SessionManager::setMessage('error', 'TC No ile eşleşen personel bulunamadı!');
    header("Location: dashboard_Anasayfa.php");
    exit;
}

// =============================================================================
// BİTMEMİŞ GÖREV KONTROLÜ
// =============================================================================
if ($personel_id > 0) {
    try {
        $gorev_kontrol_sql = "SELECT id FROM personel_gorev 
                              WHERE personel_id = ? 
                              AND (bitis_tarihi IS NULL OR bitis_tarihi = '0000-00-00' OR bitis_tarihi = '')
                              LIMIT 1";
        $stmt = $db->prepare($gorev_kontrol_sql);
        $stmt->execute([$personel_id]);
        $bitmemis_gorev_row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($bitmemis_gorev_row) {
            $has_bitmemis_gorev = true;
            SessionManager::setMessage('warning', 'Personelin bitmemiş görev kaydı olduğu için işlem yapılamaz!');
        }
    } catch (PDOException $e) {
        error_log("Bitmemiş görev kontrol hatası: " . $e->getMessage());
    }
}

// Düzenlenecek kayıt
if (isset($_GET['duzenle_id']) && $_GET['duzenle_id'] > 0) {
    $duzenle_id = (int)$_GET['duzenle_id'];
    $duzenlenecek_dosya = $database->fetch("SELECT * FROM personel_dosya_gonderme WHERE id = ?", [$duzenle_id]);
}

// Dosya listesini çek
if ($personel_id > 0) {
    try {
        $dosya_sql = "SELECT * FROM personel_dosya_gonderme WHERE personel_id = ? ORDER BY id DESC";
        $stmt = $db->prepare($dosya_sql);
        $stmt->execute([$personel_id]);
        $dosya_listesi = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Veritabanı Hatası: " . $e->getMessage());
    }
}

// =============================================================================
// KAYDETME/GÜNCELLEME
// =============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kaydet_dosya'])) {
    
    // BİTMEMİŞ GÖREV KONTROLÜ (Kaydetmeden önce tekrar kontrol)
    try {
        $gorev_kontrol_sql = "SELECT id FROM personel_gorev 
                              WHERE personel_id = ? 
                              AND (bitis_tarihi IS NULL OR bitis_tarihi = '0000-00-00' OR bitis_tarihi = '')
                              LIMIT 1";
        $stmt = $db->prepare($gorev_kontrol_sql);
        $stmt->execute([$personel_id]);
        $bitmemis_gorev_row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($bitmemis_gorev_row) {
            SessionManager::setMessage('warning', 'Personelin bitmemiş görev kaydı olduğu için işlem yapılamaz!');
            header("Location: dosya_gonderme.php?tc_search=" . urlencode($tc));
            exit;
        }
    } catch (Exception $e) {
        SessionManager::setMessage('danger', 'Kontrol hatası: ' . $e->getMessage());
        header("Location: dosya_gonderme.php?tc_search=" . urlencode($tc));
        exit;
    }
    
    try {
        $data = [
            'personel_id' => $personel_id,
            'gittigi_il' => $_POST['gittigi_il'] ?? null,
            'gittigi_ilce' => $_POST['gittigi_ilce'] ?? null,
            'gittigi_okul' => $_POST['gittigi_okul'] ?? null,
            'gonderme_tarihi' => !empty($_POST['gonderme_tarihi']) ? $_POST['gonderme_tarihi'] : null,
            'gonderme_sayisi' => !empty($_POST['gonderme_sayisi']) ? (int)$_POST['gonderme_sayisi'] : 1,
            'gonderme_cesidi' => $_POST['gonderme_cesidi'] ?? null,
            'teslim_alan_kurye' => $_POST['teslim_alan_kurye'] ?? null,
            'kurye_teslim_tarihi' => !empty($_POST['kurye_teslim_tarihi']) ? $_POST['kurye_teslim_tarihi'] : null,
            'dosya_durumu' => $_POST['dosya_durumu'] ?? 'Gönderildi',
            'teslim_tarihi' => !empty($_POST['teslim_tarihi']) ? $_POST['teslim_tarihi'] : null,
            'teslim_sayisi' => !empty($_POST['teslim_sayisi']) ? (int)$_POST['teslim_sayisi'] : 0,
            'teslim_alan' => $_POST['teslim_alan'] ?? null,
            'posta_barkod_no' => $_POST['posta_barkod_no'] ?? null,
            'raf_no' => $_POST['raf_no'] ?? null,
            'aciklama' => $_POST['aciklama'] ?? null
        ];

        $existing_id = !empty($_POST['dosya_id']) ? (int)$_POST['dosya_id'] : null;

        if ($existing_id) {
            $sql = "UPDATE personel_dosya_gonderme SET 
                    gittigi_il = :gittigi_il,
                    gittigi_ilce = :gittigi_ilce,
                    gittigi_okul = :gittigi_okul,
                    gonderme_tarihi = :gonderme_tarihi,
                    gonderme_sayisi = :gonderme_sayisi,
                    gonderme_cesidi = :gonderme_cesidi,
                    teslim_alan_kurye = :teslim_alan_kurye,
                    kurye_teslim_tarihi = :kurye_teslim_tarihi,
                    dosya_durumu = :dosya_durumu,
                    teslim_tarihi = :teslim_tarihi,
                    teslim_sayisi = :teslim_sayisi,
                    teslim_alan = :teslim_alan,
                    posta_barkod_no = :posta_barkod_no,
                    raf_no = :raf_no,
                    aciklama = :aciklama,
                    guncelleme_tarihi = NOW()
                    WHERE id = :id AND personel_id = :personel_id";
            $data['id'] = $existing_id;
            $database->query($sql, $data);
            SessionManager::setMessage('success', 'Dosya kaydı güncellendi.');
        } else {
            $sql = "INSERT INTO personel_dosya_gonderme (
                    personel_id, gittigi_il, gittigi_ilce, gittigi_okul,
                    gonderme_tarihi, gonderme_sayisi, gonderme_cesidi,
                    teslim_alan_kurye, kurye_teslim_tarihi, dosya_durumu,
                    teslim_tarihi, teslim_sayisi, teslim_alan,
                    posta_barkod_no, raf_no, aciklama,
                    kayit_tarihi, guncelleme_tarihi) 
                    VALUES (
                    :personel_id, :gittigi_il, :gittigi_ilce, :gittigi_okul,
                    :gonderme_tarihi, :gonderme_sayisi, :gonderme_cesidi,
                    :teslim_alan_kurye, :kurye_teslim_tarihi, :dosya_durumu,
                    :teslim_tarihi, :teslim_sayisi, :teslim_alan,
                    :posta_barkod_no, :raf_no, :aciklama,
                    NOW(), NOW())";
            $database->query($sql, $data);
            SessionManager::setMessage('success', 'Dosya kaydı başarıyla eklendi.');
        }
        
        header("Location: dosya_gonderme.php?tc_search=" . urlencode($tc));
        exit;
        
    } catch (Exception $e) {
        SessionManager::setMessage('danger', 'Hata: ' . $e->getMessage());
        header("Location: dosya_gonderme.php?tc_search=" . urlencode($tc));
        exit;
    }
}

// =============================================================================
// SİLME İŞLEMİ
// =============================================================================
if (isset($_POST['sil_dosya']) && isset($_POST['sil_id'])) {
    try {
        $sil_id = (int)$_POST['sil_id'];
        $database->query("DELETE FROM personel_dosya_gonderme WHERE id = ? AND personel_id = ?", [$sil_id, $personel_id]);
        SessionManager::setMessage('success', 'Dosya kaydı silindi.');
    } catch (Exception $e) {
        SessionManager::setMessage('danger', 'Silme hatası: ' . $e->getMessage());
    }
    header("Location: dosya_gonderme.php?tc_search=" . urlencode($tc));
    exit;
}

include 'head.php';
include 'header.php';
include 'sidebar.php';
include 'content.php';
?>

<div class="feedback-container">
    <?php
    $alertConfigs = ['success' => 'check-circle', 'danger' => 'exclamation-triangle', 'warning' => 'exclamation-circle', 'info' => 'info-circle'];
    foreach ($alertConfigs as $type => $icon) {
        $msg = SessionManager::getMessage($type);
        if ($msg) {
            echo '<div class="modern-alert modern-alert-'.$type.'">
                    <div class="alert-content">
                        <i class="bi bi-'.$icon.' alert-icon"></i>
                        <div class="flex-grow-1">'.htmlspecialchars($msg).'</div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                  </div>';
            SessionManager::clearMessage($type);
        }
    }
    ?>
</div>

<script>
window.aktifTc = "<?= htmlspecialchars($tc) ?>";
window.personelId = <?= $personel_id ?>;
window.simpleToken = "<?= htmlspecialchars($simpleToken ?? '', ENT_QUOTES) ?>";
window.currentPage = "dosya_gonderme.php";
window.hasBitmemisGorev = <?= $has_bitmemis_gorev ? 'true' : 'false' ?>;
</script>

<?php include 'footer.php'; ?>