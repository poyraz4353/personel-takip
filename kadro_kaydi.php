<?php
/**
 * KADRO KAYDI SİSTEMİ - Personel Takip Sistemi - kadro_kaydi.php
 * @version 1.0
 * @author Fatih
 */

require_once __DIR__ . '/config/session_manager.php';
SessionManager::start();
SessionManager::requireAuth();
$username = SessionManager::getUsername();
$user_id = SessionManager::getUserId();

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
$kadro_listesi = [];
$duzenlenecek_kadro = null;
$tc = $_GET['tc_search'] ?? $_SESSION['aktif_tc'] ?? '';
$personel_id = 0;

$pageTitle = 'Kadro Kaydı';
$pageIcon = 'star';
$welcomeMessage = 'Buradan Personelin Kadro Bilgilerini yönetebilirsiniz.';

$gunler = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];
$turkceGun = $gunler[date('w')];

$content = 'includes/kadro_kaydi_content.php';

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
// LİSTE VERİLERİNİ ÇEK
// =============================================================================
// Terfi nedenleri
$terfi_nedenleri = $database->fetchAll("SELECT id, terfi_nedeni FROM terfi_nedenleri WHERE durum = 1 ORDER BY terfi_nedeni");

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

// Düzenlenecek kayıt
if (isset($_GET['duzenle_id']) && $_GET['duzenle_id'] > 0) {
    $duzenle_id = (int)$_GET['duzenle_id'];
    $duzenlenecek_kadro = $database->fetch("SELECT * FROM personel_kadro WHERE id = ?", [$duzenle_id]);
}

// Kadro listesini çek (Terfi Tarihi'ne göre eskiden yeniye)
if ($personel_id > 0) {
    try {
        $kadro_sql = "SELECT pk.*, tn.terfi_nedeni as terfi_nedeni_adi
                      FROM personel_kadro pk
                      LEFT JOIN terfi_nedenleri tn ON pk.terfi_nedeni = tn.id
                      WHERE pk.personel_id = ? 
                      ORDER BY pk.terfi_tarihi ASC, pk.id ASC";
        $stmt = $db->prepare($kadro_sql);
        $stmt->execute([$personel_id]);
        $kadro_listesi = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Veritabanı Hatası: " . $e->getMessage());
    }
}

// =============================================================================
// KAYDETME/GÜNCELLEME
// =============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kaydet_kadro'])) {
    
    try {
        $db->beginTransaction();
        
        $data = [
            'personel_id' => $personel_id,
            'terfi_tarihi' => !empty($_POST['terfi_tarihi']) ? $_POST['terfi_tarihi'] : null,
            'terfi_nedeni' => $_POST['terfi_nedeni'] ?? null,
            'kadro_derecesi' => $_POST['kadro_derecesi'] ?? null,
            'aylik_derece' => $_POST['aylik_derece'] ?? null,
            'aylik_kademe' => $_POST['aylik_kademe'] ?? null,
            'emekli_derece' => $_POST['emekli_derece'] ?? null,
            'emekli_kadro' => $_POST['emekli_kadro'] ?? null,
            'kha_ek_gosterge' => $_POST['kha_ek_gosterge'] ?? null,
            'emekli_ek_gosterge' => $_POST['emekli_ek_gosterge'] ?? null,
            'kararname_tarihi' => !empty($_POST['kararname_tarihi']) ? $_POST['kararname_tarihi'] : null,
            'kararname_sayisi' => $_POST['kararname_sayisi'] ?? null
        ];

        $existing_id = !empty($_POST['kadro_id']) ? (int)$_POST['kadro_id'] : null;

        if ($existing_id) {
            // GÜNCELLEME
            $sql = "UPDATE personel_kadro SET 
                    terfi_tarihi = :terfi_tarihi,
                    terfi_nedeni = :terfi_nedeni,
                    kadro_derecesi = :kadro_derecesi,
                    aylik_derece = :aylik_derece,
                    aylik_kademe = :aylik_kademe,
                    emekli_derece = :emekli_derece,
                    emekli_kadro = :emekli_kadro,
                    kha_ek_gosterge = :kha_ek_gosterge,
                    emekli_ek_gosterge = :emekli_ek_gosterge,
                    kararname_tarihi = :kararname_tarihi,
                    kararname_sayisi = :kararname_sayisi,
                    guncelleme_tarihi = NOW(),
                    guncelleyen_kullanici_id = :guncelleyen_kullanici_id
                    WHERE id = :id AND personel_id = :personel_id";
            
            $params = [
                ':terfi_tarihi' => $data['terfi_tarihi'],
                ':terfi_nedeni' => $data['terfi_nedeni'],
                ':kadro_derecesi' => $data['kadro_derecesi'],
                ':aylik_derece' => $data['aylik_derece'],
                ':aylik_kademe' => $data['aylik_kademe'],
                ':emekli_derece' => $data['emekli_derece'],
                ':emekli_kadro' => $data['emekli_kadro'],
                ':kha_ek_gosterge' => $data['kha_ek_gosterge'],
                ':emekli_ek_gosterge' => $data['emekli_ek_gosterge'],
                ':kararname_tarihi' => $data['kararname_tarihi'],
                ':kararname_sayisi' => $data['kararname_sayisi'],
                ':guncelleyen_kullanici_id' => $user_id,
                ':id' => $existing_id,
                ':personel_id' => $personel_id
            ];
            
            $database->query($sql, $params);
            SessionManager::setMessage('success', 'Kadro kaydı güncellendi.');
            
        } else {
            // YENİ KAYIT
            $sql = "INSERT INTO personel_kadro (
                    personel_id, terfi_tarihi, terfi_nedeni,
                    kadro_derecesi, aylik_derece, aylik_kademe, 
                    emekli_derece, emekli_kadro, kha_ek_gosterge, emekli_ek_gosterge,
                    kararname_tarihi, kararname_sayisi,
                    kayit_tarihi, guncelleme_tarihi, ekleyen_kullanici_id, guncelleyen_kullanici_id) 
                    VALUES (
                    :personel_id, :terfi_tarihi, :terfi_nedeni,
                    :kadro_derecesi, :aylik_derece, :aylik_kademe,
                    :emekli_derece, :emekli_kadro, :kha_ek_gosterge, :emekli_ek_gosterge,
                    :kararname_tarihi, :kararname_sayisi,
                    NOW(), NOW(), :ekleyen_kullanici_id, :guncelleyen_kullanici_id)";

            $params = [
                ':personel_id' => $personel_id,
                ':terfi_tarihi' => $data['terfi_tarihi'],
                ':terfi_nedeni' => $data['terfi_nedeni'],
                ':kadro_derecesi' => $data['kadro_derecesi'],
                ':aylik_derece' => $data['aylik_derece'],
                ':aylik_kademe' => $data['aylik_kademe'],
                ':emekli_derece' => $data['emekli_derece'],
                ':emekli_kadro' => $data['emekli_kadro'],
                ':kha_ek_gosterge' => $data['kha_ek_gosterge'],
                ':emekli_ek_gosterge' => $data['emekli_ek_gosterge'],
                ':kararname_tarihi' => $data['kararname_tarihi'],
                ':kararname_sayisi' => $data['kararname_sayisi'],
                ':ekleyen_kullanici_id' => $user_id,
                ':guncelleyen_kullanici_id' => $user_id
            ];

            $database->query($sql, $params);
            SessionManager::setMessage('success', 'Kadro kaydı başarıyla eklendi.');
        }
        
        // Personel tablosunu güncelle
        $database->query("UPDATE personel SET guncelleme_tarihi = NOW(), guncelleyen_kullanici = ? WHERE id = ?", [$username, $personel_id]);
        
        $db->commit();
        header("Location: kadro_kaydi.php?tc_search=" . urlencode($tc));
        exit;
        
    } catch (Exception $e) {
        $db->rollBack();
        SessionManager::setMessage('danger', 'Hata: ' . $e->getMessage());
        header("Location: kadro_kaydi.php?tc_search=" . urlencode($tc));
        exit;
    }
}

// =============================================================================
// SİLME İŞLEMİ
// =============================================================================
if (isset($_POST['sil_kadro']) && isset($_POST['sil_id'])) {
    try {
        $sil_id = (int)$_POST['sil_id'];
        $database->query("DELETE FROM personel_kadro WHERE id = ? AND personel_id = ?", [$sil_id, $personel_id]);
        
        // Personel tablosunu güncelle (son güncelleyen kullanıcı)
        $database->query("UPDATE personel SET guncelleme_tarihi = NOW(), guncelleyen_kullanici = ? WHERE id = ?", [$username, $personel_id]);
        
        SessionManager::setMessage('success', 'Kadro kaydı silindi.');
    } catch (Exception $e) {
        SessionManager::setMessage('danger', 'Silme hatası: ' . $e->getMessage());
    }
    header("Location: kadro_kaydi.php?tc_search=" . urlencode($tc));
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
window.currentPage = "kadro_kaydi.php";
</script>

<?php include 'footer.php'; ?>