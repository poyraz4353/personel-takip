<?php
/**
 * SÖZLEŞME KAYDI SİSTEMİ - Personel Takip Sistemi - sozlesme_kaydi.php
 * @version 1.0
 * @author Fatih
 */

require_once __DIR__ . '/config/session_manager.php';
SessionManager::start();
SessionManager::requireAuth();
$username = SessionManager::getUsername();

require_once __DIR__ . '/config/db_config.php';
$database = Database::getInstance();
$db = $database->getConnection();

// =============================================================================
// CSRF KORUMASI - DOĞRULAMA
// =============================================================================
function generateSimpleToken() {
    if (!isset($_SESSION['simple_token'])) {
        $_SESSION['simple_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['simple_token'];
}

function validateSimpleToken($token) {
    if (isset($_SESSION['simple_token']) && !empty($token) && $_SESSION['simple_token'] === $token) {
        unset($_SESSION['simple_token']);
        return true;
    }
    return false;
}

$simpleToken = generateSimpleToken();

$personel = null;
$sozlesmeler = [];
$tc = $_GET['tc_search'] ?? $_SESSION['aktif_tc'] ?? '';
$personel_id = 0;

// Düzenlenecek sözleşme
$duzenlenecek_sozlesme = null;
if (isset($_GET['duzenle_id']) && $_GET['duzenle_id'] > 0) {
    $duzenle_id = (int)$_GET['duzenle_id'];
    $duzenlenecek_sozlesme = $database->fetch("SELECT * FROM personel_sozlesme WHERE id = ?", [$duzenle_id]);
}

$pageTitle = 'Sözleşme Kaydı';
$pageIcon = 'file-contract';
$welcomeMessage = 'Buradan Personel Sözleşme Kayıtlarını yönetebilir, düzenleyebilirsiniz.';

$gunler = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];
$turkceGun = $gunler[date('w')];

$content = 'includes/sozlesme_kaydi_content.php';

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

// İlçeler ve okullar (düzenleme için)
$ilceler = [];
$okullar = [];
if (isset($duzenlenecek_sozlesme) && !empty($duzenlenecek_sozlesme['sozlesme_il_adi'])) {
    $il = $database->fetch("SELECT id FROM iller WHERE il_adi = ?", [$duzenlenecek_sozlesme['sozlesme_il_adi']]);
    if ($il) {
        $ilceler = $database->fetchAll("SELECT id, ilce_adi FROM ilceler WHERE il_id = ? ORDER BY ilce_adi", [$il['id']]);
    }
}
if (isset($duzenlenecek_sozlesme) && !empty($duzenlenecek_sozlesme['sozlesme_ilce_adi']) && isset($il['id'])) {
    $ilce = $database->fetch("SELECT id FROM ilceler WHERE ilce_adi = ? AND il_id = ?", [$duzenlenecek_sozlesme['sozlesme_ilce_adi'], $il['id']]);
    if ($ilce) {
        $okullar = $database->fetchAll("SELECT kurum_kodu, gorev_yeri, okul_tur FROM okullar WHERE ilce_id = ? ORDER BY gorev_yeri", [$ilce['id']]);
    }
}

// Sözleşmeleri çek
if ($personel_id > 0) {
    try {
        $sozlesme_sql = "SELECT * FROM personel_sozlesme WHERE personel_id = ? ORDER BY id ASC";
        $stmt = $db->prepare($sozlesme_sql);
        $stmt->execute([$personel_id]);
        $sozlesmeler = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Veritabanı Hatası: " . $e->getMessage());
    }
}

// =============================================================================
// SÖZLEŞME KAYDETME/GÜNCELLEME
// =============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kaydet_sozlesme'])) {
    try {
        $kurum_kodu = $_POST['gorev_kurum_kodu'] ?? null;
        $sozlesme_okul_adi = null;
        $sozlesme_okul_tur = null;

        if ($kurum_kodu) {
            $okul_res = $database->fetch("SELECT gorev_yeri, okul_tur FROM okullar WHERE kurum_kodu = ?", [$kurum_kodu]);
            $sozlesme_okul_adi = $okul_res['gorev_yeri'] ?? null;
            $sozlesme_okul_tur = $okul_res['okul_tur'] ?? null;
        }

        // İl ve ilçe adlarını ID'den çek
        $il_adi = null;
        if (!empty($_POST['il_id'])) {
            $il = $database->fetch("SELECT il_adi FROM iller WHERE id = ?", [$_POST['il_id']]);
            $il_adi = $il['il_adi'] ?? null;
        }
        
        $ilce_adi = null;
        if (!empty($_POST['ilce_id'])) {
            $ilce = $database->fetch("SELECT ilce_adi FROM ilceler WHERE id = ?", [$_POST['ilce_id']]);
            $ilce_adi = $ilce['ilce_adi'] ?? null;
        }

		$sozlesme_data = [
			'personel_id'               => $personel_id,
			'sozlesme_il_adi'           => $il_adi,
			'sozlesme_ilce_adi'         => $ilce_adi,
			'sozlesme_okul_adi'         => $sozlesme_okul_adi,
			'sozlesme_okul_tur'         => $sozlesme_okul_tur,
			'sozlesme_kurum_kodu'       => $kurum_kodu,
			'sozlesme_kapali_kurum'     => isset($_POST['kapali_kurum']) ? 1 : 0,
			'sozlesme_turu'             => $_POST['sozlesme_turu'] ?? null,
			'sozlesmeli_baslama_tarihi' => !empty($_POST['baslama_tarihi']) ? $_POST['baslama_tarihi'] : null,
			'sozlesmeli_bitis_tarihi'   => !empty($_POST['bitis_tarihi']) ? $_POST['bitis_tarihi'] : null,
			'ayrilma_nedeni'            => $_POST['ayrilma_nedeni'] ?? null,
		];

        $existing_id = !empty($_POST['sozlesme_id']) ? (int)$_POST['sozlesme_id'] : null;

		if ($existing_id) {
			$mevcut = $database->fetch("SELECT * FROM personel_sozlesme WHERE id = ?", [$existing_id]);
										
            $sozlesme_data['sozlesme_il_adi'] = $_POST['sozlesme_il_adi'] ?? $mevcut['sozlesme_il_adi'] ?? null;
            $sozlesme_data['sozlesme_ilce_adi'] = $_POST['sozlesme_ilce_adi'] ?? $mevcut['sozlesme_ilce_adi'] ?? null;
            $sozlesme_data['sozlesme_okul_adi'] = $_POST['sozlesme_okul_adi'] ?? $mevcut['sozlesme_okul_adi'] ?? null;
            $sozlesme_data['sozlesme_kurum_kodu'] = $_POST['sozlesme_kurum_kodu'] ?? $mevcut['sozlesme_kurum_kodu'] ?? null;
            $sozlesme_data['sozlesme_kapali_kurum'] = isset($_POST['kapali_kurum']) ? 1 : ($mevcut['sozlesme_kapali_kurum'] ?? 0);
            
			$sql = "UPDATE personel_sozlesme SET 
					sozlesme_il_adi = :sozlesme_il_adi, 
					sozlesme_ilce_adi = :sozlesme_ilce_adi, 
					sozlesme_okul_adi = :sozlesme_okul_adi,
					sozlesme_okul_tur = :sozlesme_okul_tur,
					sozlesme_kurum_kodu = :sozlesme_kurum_kodu, 
					sozlesme_kapali_kurum = :sozlesme_kapali_kurum,
					sozlesme_turu = :sozlesme_turu,
					sozlesmeli_baslama_tarihi = :sozlesmeli_baslama_tarihi,
					sozlesmeli_bitis_tarihi = :sozlesmeli_bitis_tarihi,
					ayrilma_nedeni = :ayrilma_nedeni,
					guncelleme_tarihi = NOW()
					WHERE id = :id AND personel_id = :personel_id";
		
            $sozlesme_data['id'] = $existing_id;
            $database->query($sql, $sozlesme_data);
            SessionManager::setMessage('success', 'Sözleşme kaydı güncellendi.');
        } else {
			$sql = "INSERT INTO personel_sozlesme (
					personel_id, sozlesme_il_adi, sozlesme_ilce_adi, sozlesme_okul_adi,
					sozlesme_okul_tur, sozlesme_kurum_kodu, sozlesme_kapali_kurum,
					sozlesme_turu, sozlesmeli_baslama_tarihi, sozlesmeli_bitis_tarihi,
					ayrilma_nedeni, kayit_tarihi, guncelleme_tarihi) 
					VALUES (
					:personel_id, :sozlesme_il_adi, :sozlesme_ilce_adi, :sozlesme_okul_adi,
					:sozlesme_okul_tur, :sozlesme_kurum_kodu, :sozlesme_kapali_kurum,
					:sozlesme_turu, :sozlesmeli_baslama_tarihi, :sozlesmeli_bitis_tarihi,
					:ayrilma_nedeni, NOW(), NOW())";
		
            $database->query($sql, $sozlesme_data);
            SessionManager::setMessage('success', 'Yeni sözleşme kaydı başarıyla eklendi.');
        }
        
        header("Location: sozlesme_kaydi.php?tc_search=" . urlencode($tc));
        exit;
        
    } catch (Exception $e) {
        SessionManager::setMessage('danger', 'Hata: ' . $e->getMessage());
        header("Location: sozlesme_kaydi.php?tc_search=" . urlencode($tc));
        exit;
    }
}

// Liste verileri
$iller = $database->fetchAll("SELECT id, il_adi FROM iller ORDER BY il_adi");
$sozlesme_turleri = ['Aday Sözleşmeli Öğretmen', 'Sözleşmeli Öğretmen(657 S.K. 4/B)', 'Sözleşmeli Personel'];
$durumlar = $database->fetchAll("SELECT id, durum_adi FROM durumu WHERE aktif = 1 ORDER BY id ASC");

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
window.currentPage = "sozlesme_kaydi.php";
</script>

<?php include 'footer.php'; ?>